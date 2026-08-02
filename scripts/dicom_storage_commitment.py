#!/opt/registry-dicom/bin/python
"""Request storage commitment for one previously stored synthetic object."""

import json
import sys
import threading

from pydicom.dataset import Dataset
from pydicom.sequence import Sequence
from pydicom.uid import generate_uid
from pynetdicom import AE, build_role, evt
from pynetdicom.sop_class import StorageCommitmentPushModel

PUSH_MODEL_INSTANCE_UID = "1.2.840.10008.1.20.1.1"
UID_PREFIX = "1.2.826.0.1.3680043.10.987."


def main():
    request = json.load(sys.stdin)
    transaction_uid = generate_uid(prefix=UID_PREFIX)
    received = threading.Event()
    report = {}

    def handle_event(event):
        information = event.event_information
        if str(getattr(information, "TransactionUID", "")) != transaction_uid:
            return 0x0115, None

        success = list(getattr(information, "ReferencedSOPSequence", []))
        failed = list(getattr(information, "FailedSOPSequence", []))
        report.update({
            "event_type": event.event_type,
            "committed": any(str(item.ReferencedSOPInstanceUID) == request["sop_instance_uid"] for item in success),
            "failed": any(str(item.ReferencedSOPInstanceUID) == request["sop_instance_uid"] for item in failed),
            "failure_reason": next((int(item.FailureReason) for item in failed if str(item.ReferencedSOPInstanceUID) == request["sop_instance_uid"]), None),
        })
        received.set()
        return 0x0000, None

    handlers = [(evt.EVT_N_EVENT_REPORT, handle_event)]
    callback_ae = AE(ae_title=request["calling_ae_title"])
    callback_ae.add_supported_context(StorageCommitmentPushModel)
    server = callback_ae.start_server(
        (request.get("callback_bind", "0.0.0.0"), int(request["callback_port"])),
        block=False,
        evt_handlers=handlers,
    )

    association = None
    try:
        ae = AE(ae_title=request["calling_ae_title"])
        ae.acse_timeout = 5
        ae.dimse_timeout = 20
        ae.network_timeout = 5
        ae.add_requested_context(StorageCommitmentPushModel)
        role = build_role(StorageCommitmentPushModel, scu_role=True, scp_role=True)
        association = ae.associate(
            request["host"],
            int(request["port"]),
            ae_title=request["called_ae_title"],
            ext_neg=[role],
            evt_handlers=handlers,
        )
        if not association.is_established:
            print(json.dumps({"successful": False, "failure_type": "association_rejected", "transaction_uid": transaction_uid, "message": "DICOM association could not be established"}))
            return 2

        reference = Dataset()
        reference.ReferencedSOPClassUID = request["sop_class_uid"]
        reference.ReferencedSOPInstanceUID = request["sop_instance_uid"]
        action = Dataset()
        action.TransactionUID = transaction_uid
        action.ReferencedSOPSequence = Sequence([reference])
        status, _ = association.send_n_action(action, 1, StorageCommitmentPushModel, PUSH_MODEL_INSTANCE_UID)
        action_status = int(status.Status) if status and hasattr(status, "Status") else None
        if action_status != 0x0000:
            print(json.dumps({"successful": False, "failure_type": "n_action_failed", "action_status": action_status, "transaction_uid": transaction_uid, "message": "Storage Commitment N-ACTION failed"}))
            return 3

        if not received.wait(float(request["event_timeout_seconds"])):
            print(json.dumps({"successful": False, "failure_type": "event_report_timeout", "action_status": action_status, "transaction_uid": transaction_uid, "message": "No N-EVENT-REPORT received before timeout"}))
            return 4

        successful = bool(report.get("committed")) and not bool(report.get("failed"))
        print(json.dumps({"successful": successful, "failure_type": None if successful else "commitment_failed", "action_status": action_status, "transaction_uid": transaction_uid, "message": "Storage commitment confirmed" if successful else "Storage commitment rejected", **report}))
        return 0 if successful else 5
    finally:
        if association is not None and association.is_established:
            association.release()
        server.shutdown()


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exception:
        print(json.dumps({"successful": False, "failure_type": "process_error", "message": str(exception)}))
        sys.exit(1)
