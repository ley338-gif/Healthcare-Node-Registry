#!/opt/registry-dicom/bin/python
"""Run an authorized synthetic MPPS N-CREATE/N-SET diagnostic."""

import json
import sys
from datetime import datetime

from pydicom.dataset import Dataset
from pydicom.sequence import Sequence
from pydicom.uid import generate_uid
from pynetdicom import AE
from pynetdicom.sop_class import ModalityPerformedProcedureStep

UID_PREFIX = "1.2.826.0.1.3680043.10.987."


def status_code(status):
    return None if status is None else int(status.Status)


def main():
    request = json.load(sys.stdin)
    now = datetime.now()
    instance_uid = generate_uid(prefix=UID_PREFIX)
    step_id = f"HNR-MPPS-{now:%Y%m%d%H%M%S}"

    create = Dataset()
    create.SpecificCharacterSet = "ISO_IR 192"
    create.PerformedProcedureStepID = step_id
    create.PerformedStationAETitle = request["calling_ae_title"]
    create.PerformedProcedureStepStartDate = now.strftime("%Y%m%d")
    create.PerformedProcedureStepStartTime = now.strftime("%H%M%S")
    create.PerformedProcedureStepStatus = "IN PROGRESS"
    create.PerformedProcedureStepDescription = "HNR synthetic diagnostic"
    create.PerformedProcedureTypeDescription = "DIAGNOSTIC TEST"
    create.Modality = "OT"
    create.PatientName = "HNRTEST^SYNTHETIC"
    create.PatientID = step_id
    create.PatientBirthDate = "19000101"
    create.PatientSex = "O"

    scheduled = Dataset()
    scheduled.AccessionNumber = "HNR-TEST"
    scheduled.RequestedProcedureID = step_id
    scheduled.ScheduledProcedureStepID = step_id
    scheduled.StudyInstanceUID = generate_uid(prefix=UID_PREFIX)
    scheduled.ReferencedStudySequence = Sequence([])
    scheduled.ScheduledProtocolCodeSequence = Sequence([])
    create.ScheduledStepAttributesSequence = Sequence([scheduled])
    create.PerformedSeriesSequence = Sequence([])

    ae = AE(ae_title=request["calling_ae_title"])
    ae.acse_timeout = 5
    ae.dimse_timeout = 20
    ae.network_timeout = 5
    ae.add_requested_context(ModalityPerformedProcedureStep)
    association = ae.associate(request["host"], int(request["port"]), ae_title=request["called_ae_title"])
    if not association.is_established:
        print(json.dumps({"successful": False, "failure_type": "association_rejected", "message": "DICOM association could not be established", "instance_uid": instance_uid}))
        return 2

    try:
        created, _ = association.send_n_create(create, ModalityPerformedProcedureStep, instance_uid)
        create_status = status_code(created)
        if create_status not in (0x0000, 0xB000):
            print(json.dumps({"successful": False, "failure_type": "n_create_failed", "message": "MPPS N-CREATE failed", "create_status": create_status, "instance_uid": instance_uid}))
            return 3

        completed = Dataset()
        completed.PerformedProcedureStepStatus = "COMPLETED"
        completed.PerformedProcedureStepEndDate = now.strftime("%Y%m%d")
        completed.PerformedProcedureStepEndTime = now.strftime("%H%M%S")
        completed.PerformedSeriesSequence = Sequence([])
        updated, _ = association.send_n_set(completed, ModalityPerformedProcedureStep, instance_uid)
        set_status = status_code(updated)
        successful = set_status in (0x0000, 0xB000)
        print(json.dumps({"successful": successful, "failure_type": None if successful else "n_set_failed", "message": "MPPS completed" if successful else "MPPS N-SET failed", "create_status": create_status, "set_status": set_status, "instance_uid": instance_uid}))
        return 0 if successful else 4
    finally:
        association.release()


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exception:
        print(json.dumps({"successful": False, "failure_type": "process_error", "message": str(exception)}))
        sys.exit(1)
