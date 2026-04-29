import sys
import json
from deepface import DeepFace

try:
    if len(sys.argv) < 3:
        raise Exception("Usage: face_verify.py <id_path> <selfie_path>")

    id_image = sys.argv[1]
    selfie_image = sys.argv[2]

    # model_name="VGG-Face" is the industry standard for this type of verification
    result = DeepFace.verify(id_image, selfie_image, enforce_detection=True, model_name="VGG-Face")

    # DeepFace 'distance' (0 to 1). We convert to 0-100 confidence.
    confidence = round((1 - result["distance"]) * 100, 2)

    # Business logic for auto-verification threshold
    status = "verified" if confidence >= 75 else "manual_review"

    response = {
        "status": status,
        "verified": result["verified"],
        "confidence": confidence,
        "distance": result["distance"]
    }

    print(json.dumps(response))

except Exception as e:
    print(json.dumps({
        "status": "error",
        "error": str(e)
    }))