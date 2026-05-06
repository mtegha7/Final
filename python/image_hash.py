import sys
import json
import os

try:
    from PIL import Image
    import imagehash
except ImportError:
    # Emit a clear error so PHP can surface it rather than silently returning null
    print(json.dumps({"error": "Missing dependencies: pip install Pillow imagehash"}))
    sys.exit(1)

try:
    if len(sys.argv) < 2:
        raise Exception("Usage: image_hash.py <image_path>")

    image_path = sys.argv[1]

    if not os.path.exists(image_path):
        raise Exception(f"Image file not found: {image_path}")

    img = Image.open(image_path).convert("RGB")

    # Perceptual hash — tolerant of minor resizing/compression differences.
    # 16x16 grid gives a 256-bit hash; two images with hamming distance <= 10
    # are considered duplicates by convention.
    phash = imagehash.phash(img, hash_size=16)

    # Also compute average hash as a secondary signal
    ahash = imagehash.average_hash(img, hash_size=16)

    # Output both as hex strings, separated by a pipe so PHP can split if needed.
    # The primary value returned is phash — PHP reads trim($output) directly.
    print(str(phash))

except Exception as e:
    # PHP reads trim(shell_exec(...)) — an empty string on error causes a silent
    # null hash in the DB. Print a clearly invalid sentinel so the controller
    # can detect failure and bail out cleanly.
    sys.stderr.write(f"image_hash error: {str(e)}\n")
    print("HASH_ERROR")
    sys.exit(1)