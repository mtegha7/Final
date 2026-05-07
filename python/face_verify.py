import sys
import json
import os
from deepface import DeepFace

try:

    if len(sys.argv) < 3:
        raise Exception("Usage: face_verify.py <id_path> <selfie_path>")

    id_image = sys.argv[1]
    selfie_image = sys.argv[2]

    if not os.path.exists(id_image):
        raise Exception(f"ID image not found: {id_image}")

    if not os.path.exists(selfie_image):
        raise Exception(f"Selfie image not found: {selfie_image}")

    result = DeepFace.verify(
        img1_path=id_image,
        img2_path=selfie_image,
        model_name="Facenet512",
        detector_backend="retinaface",
        enforce_detection=False
    )

    distance = float(result["distance"])

    confidence = max(
        0,
        min(
            100,
            round((1 - distance) * 100, 2)
        )
    )

    response = {
        "status": "success",
        "verified": bool(result["verified"]),
        "confidence": confidence,
        "distance": distance
    }

    print(json.dumps(response))

except Exception as e:

    error_response = {
        "status": "error",
        "error": str(e)
    }

    print(json.dumps(error_response))

    sys.exit(1)