import os
import re

project_dir = "."

db_patterns = [
    r"\bquery\s*\(",
    r"\bprepare\s*\(",
    r"\bexecute\s*\(",
    r"\bmysqli_query\s*\(",
    r"\b->query\s*\(",
    r"\b->prepare\s*\(",
    r"\b->exec\s*\(",
]

print("Scanning PHP files and commenting out database queries...")

for root, dirs, files in os.walk(project_dir):
  if ".git" in root or "__MACOSX" in root:
    continue
  for file in files:
    if file.endswith(".php"):
      file_path = os.path.join(root, file)
      try:
        with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
          lines = f.readlines()

        modified = False
        new_lines = []
        for line in lines:
          trimmed = line.strip()
          is_db_line = any(re.search(pat, line) for pat in db_patterns)
          if (
              is_db_line
              and not trimmed.startswith("//")
              and not trimmed.startswith("#")
              and not trimmed.startswith("/*")
              and not trimmed.startswith("*")
          ):
            indent = line[: len(line) - len(line.lstrip())]
            new_lines.append(
                f"{indent}// Database query disabled for commit\n"
            )
            new_lines.append(f"{indent}// {trimmed}\n")
            modified = True
          else:
            new_lines.append(line)

        if modified:
          with open(file_path, "w", encoding="utf-8") as f:
            f.writelines(new_lines)
          print(f"[UPDATED] {file_path}")

      except Exception as e:
        print(f"Error reading {file_path}: {e}")

print("Done! All database queries have been commented out.")
