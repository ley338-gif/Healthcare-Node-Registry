#!/usr/bin/env python3

import json
import sys
from datetime import datetime, timezone

from pydicom.dataset import FileDataset, FileMetaDataset
from pydicom.uid import ExplicitVRLittleEndian, PYDICOM_IMPLEMENTATION_UID


SOP_CLASSES = {
    "secondary_capture": ("1.2.840.10008.5.1.4.1.1.7", "OT"),
    "ct_image": ("1.2.840.10008.5.1.4.1.1.2", "CT"),
    "mr_image": ("1.2.840.10008.5.1.4.1.1.4", "MR"),
}


def dataset(payload: dict[str, str]) -> FileDataset:
    sop_class_uid, modality = SOP_CLASSES[payload["sop_class"]]
    file_meta = FileMetaDataset()
    file_meta.MediaStorageSOPClassUID = sop_class_uid
    file_meta.MediaStorageSOPInstanceUID = payload["sop_instance_uid"]
    file_meta.TransferSyntaxUID = ExplicitVRLittleEndian
    file_meta.ImplementationClassUID = PYDICOM_IMPLEMENTATION_UID

    result = FileDataset(payload["output_path"], {}, file_meta=file_meta, preamble=b"\0" * 128)
    now = datetime.now(timezone.utc)
    result.SOPClassUID = sop_class_uid
    result.SOPInstanceUID = payload["sop_instance_uid"]
    result.StudyInstanceUID = payload["study_instance_uid"]
    result.SeriesInstanceUID = payload["series_instance_uid"]
    result.FrameOfReferenceUID = payload["frame_of_reference_uid"]
    result.PatientName = "DICOMNODE^TEST"
    result.PatientID = payload["patient_id"]
    result.PatientBirthDate = ""
    result.PatientSex = "O"
    result.StudyDate = now.strftime("%Y%m%d")
    result.StudyTime = now.strftime("%H%M%S")
    result.ContentDate = result.StudyDate
    result.ContentTime = result.StudyTime
    result.AccessionNumber = ""
    result.ReferringPhysicianName = ""
    result.StudyID = "SYNTHETIC"
    result.StudyDescription = "DICOM Connectivity Test"
    result.SeriesNumber = 1
    result.InstanceNumber = 1
    result.Modality = modality
    result.Manufacturer = "Healthcare Node Registry"
    result.Rows = 1
    result.Columns = 1
    result.SamplesPerPixel = 1
    result.PhotometricInterpretation = "MONOCHROME2"
    result.BitsAllocated = 16
    result.BitsStored = 12
    result.HighBit = 11
    result.PixelRepresentation = 0
    result.PixelData = b"\0\0"

    if payload["sop_class"] == "secondary_capture":
        result.ConversionType = "SYN"
    else:
        result.ImageType = ["ORIGINAL", "PRIMARY", "AXIAL"]
        result.PatientPosition = "HFS"
        result.ImagePositionPatient = ["0", "0", "0"]
        result.ImageOrientationPatient = ["1", "0", "0", "0", "1", "0"]
        result.PixelSpacing = ["1", "1"]
        result.SliceThickness = "1"

    if payload["sop_class"] == "ct_image":
        result.KVP = "120"
        result.RescaleIntercept = "0"
        result.RescaleSlope = "1"
        result.RescaleType = "HU"

    if payload["sop_class"] == "mr_image":
        result.ScanningSequence = "SE"
        result.SequenceVariant = "NONE"
        result.ScanOptions = ""
        result.MRAcquisitionType = "2D"
        result.RepetitionTime = "500"
        result.EchoTime = "10"
        result.EchoTrainLength = 1
        result.MagneticFieldStrength = "1.5"
        result.SpacingBetweenSlices = "1"

    return result


def main() -> int:
    payload = json.load(sys.stdin)
    if payload.get("sop_class") not in SOP_CLASSES:
        raise ValueError("Unsupported synthetic Storage SOP Class")

    result = dataset(payload)
    result.save_as(payload["output_path"], enforce_file_format=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
