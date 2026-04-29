import sys
import json
from deepface import DeepFace

try:
    id_image = sys.argv[1]
    selfie_image = sys.argv[2]

    result = DeepFace.verify(id_image, selfie_image, enforce_detection=False)

    confidence = round((1 - result["distance"]) * 100, 2)

    # High confidence = auto verified, everything else goes to admin
    if confidence >= 70:
        status = "verified"
    else:
        status = "manual_review"

    response = {
        "status": status,
        "verified": result["verified"],
        "distance": result["distance"],
        "confidence": confidence
    }

    print(json.dumps(response))

except Exception as e:
    print(json.dumps({
        "status": "error",
        "error": str(e)
    }))