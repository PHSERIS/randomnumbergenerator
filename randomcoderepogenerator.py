import zipfile
import json
import os
import sys

# --------- Configuration ---------
# Path to your PHP file
php_file_path = ""  #
module_name = "random_code_action_tag_repo"
class_name = "RandomCodeActionTagRepo"
namespace = "MGB\\RandomNumberActionTagRepo"
version = "1.0.0"

# --------- Load PHP Code ---------
if not os.path.exists(php_file_path):
    print(f"Error: PHP file not found at {php_file_path}")
    sys.exit(1)

with open(php_file_path, "r") as f:
    php_code = f.read()

# --------- Create config.json ---------
config_json = {
    "name": module_name,
    "namespace": namespace,
    "version": version,
    "authors": [
        {
            "name": "David Clark, Devin Foley",
            "institution": "Mass General Brigham, McMaster University"
        }
    ],
    "description": "Adds @RANDOMCODE(LENGTH,TYPE) action tag to generate unique alpha or alphanumeric codes (1–12 characters).",
    "project-settings": [],
    "system-settings": []
}

# --------- Write ZIP file ---------
zip_filename = f"{module_name}_v{version}.zip"
with zipfile.ZipFile(zip_filename, "w") as zipf:
    zipf.writestr("config.json", json.dumps(config_json, indent=4))
    zipf.writestr(f"{class_name}.php", php_code)

print(f"✅ Module packaged: {zip_filename}")