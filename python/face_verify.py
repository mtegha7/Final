import sys
import json
import os
from deepface import DeepFace

try:
    if len(sys.argv) < 3:
        raise Exception("Usage: face_verify.py <id_path> <selfie_path>")

    id_image = sys.argv[1]
    selfie_image = sys.argv[2]

    # Verify files exist
    if not os.path.exists(id_image):
        raise Exception(f"ID image not found: {id_image}")
    
    if not os.path.exists(selfie_image):
        raise Exception(f"Selfie image not found: {selfie_image}")

    # model_name="VGG-Face" is the industry standard for this type of verification
    result = DeepFace.verify(
        id_image, 
        selfie_image, 
        enforce_detection=True, 
        model_name="VGG-Face",
        detector_backend="opencv"  # More reliable than default
    )

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
    error_response = {
        "status": "error",
        "error": str(e)
    }
    print(json.dumps(error_response))
    sys.exit(1)