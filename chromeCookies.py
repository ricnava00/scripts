import sys
import os
import tempfile
import json
import base64
import sqlite3
import shutil
import json
import win32crypt # pip install pypiwin32
from Crypto.Cipher import AES # pip install pycryptodome
from shadowcopy import shadow_copy
from ctypes import windll, byref, create_unicode_buffer, pointer, WINFUNCTYPE
from ctypes.wintypes import DWORD, WCHAR, UINT
import pyuac

def unlock_cookies(cookies_path):
    ERROR_SUCCESS = 0
    ERROR_MORE_DATA  = 234
    RmForceShutdown = 1

    @WINFUNCTYPE(None, UINT)
    def callback(percent_complete: UINT) -> None:
        pass
    rstrtmgr = windll.LoadLibrary("Rstrtmgr")
    session_handle = DWORD(0)
    session_flags = DWORD(0)
    session_key = (WCHAR * 256)()

    result = DWORD(rstrtmgr.RmStartSession(byref(session_handle), session_flags, session_key)).value

    if result != ERROR_SUCCESS:
        raise RuntimeError(f"RmStartSession returned non-zero result: {result}")

    try:
        result = DWORD(rstrtmgr.RmRegisterResources(session_handle, 1, byref(pointer(create_unicode_buffer(cookies_path))), 0, None, 0, None)).value

        if result != ERROR_SUCCESS:
            raise RuntimeError(f"RmRegisterResources returned non-zero result: {result}")

        proc_info_needed = DWORD(0)
        proc_info = DWORD(0)
        reboot_reasons = DWORD(0)

        result = DWORD(rstrtmgr.RmGetList(session_handle, byref(proc_info_needed), byref(proc_info), None, byref(reboot_reasons))).value

        if result not in (ERROR_SUCCESS, ERROR_MORE_DATA):
            raise RuntimeError(f"RmGetList returned non-successful result: {result}")

        if proc_info_needed.value:
            result = DWORD(rstrtmgr.RmShutdown(session_handle, RmForceShutdown, callback)).value

            if result != ERROR_SUCCESS:
                raise RuntimeError(f"RmShutdown returned non-successful result: {result}")
        # else:
            # print("File is not locked")
    finally:
        result = DWORD(rstrtmgr.RmEndSession(session_handle)).value

        if result != ERROR_SUCCESS:
            raise RuntimeError(f"RmEndSession returned non-successful result: {result}")

def get_encryption_key():
    local_state_path = os.path.join(os.environ["USERPROFILE"],
                                    "AppData", "Local", "Google", "Chrome",
                                    "User Data", "Local State")
    with open(local_state_path, "r", encoding="utf-8") as f:
        local_state = f.read()
        local_state = json.loads(local_state)
    key = base64.b64decode(local_state["os_crypt"]["encrypted_key"])
    key = key[5:]
    return win32crypt.CryptUnprotectData(key, None, None, None, 0)[1]

def decrypt_data(data, key):
    try:
        iv = data[3:15]
        data = data[15:]
        cipher = AES.new(key, AES.MODE_GCM, iv)
        return cipher.decrypt(data)[:-16].decode()
    except:
        try:
            return str(win32crypt.CryptUnprotectData(data, None, None, None, 0)[1])
        except:
            return ""

def unlock(db_path,temp_path):
    try:
        shutil.copyfile(db_path,temp_path)
    except:
        try:
            raise RuntimeError("Skipping: this deletes session cookies")
            unlock_cookies(db_path)
            shutil.copyfile(db_path,temp_path)
        except:
            shadow_copy(db_path,temp_path)

@pyuac.main_requires_admin(scan_for_error=[])
def main():
    user="Profile 2"
    db_path = os.path.join(os.environ["USERPROFILE"], "AppData", "Local",
                            "Google", "Chrome", "User Data", user, "Network", "Cookies")
    with tempfile.TemporaryDirectory() as temp:
        temp_path=os.path.join(temp,"Cookies")
        unlock(db_path,temp_path)
        db = sqlite3.connect(temp_path)
        db.text_factory = lambda b: b.decode(errors="ignore")
        cursor = db.cursor()
        cursor.execute("""
        SELECT host_key, name, value, creation_utc, last_access_utc, expires_utc, encrypted_value 
        FROM cookies""")
        cookies=[]
        key = get_encryption_key()
        for host_key, name, value, creation_utc, last_access_utc, expires_utc, encrypted_value in cursor.fetchall():
            if not value:
                decrypted_value = decrypt_data(encrypted_value, key)
            else:
                decrypted_value = value
            cookies.append({'host':host_key,'name':name,'value':decrypted_value})
        db.close()
        print(json.dumps(cookies))

if __name__ == "__main__":
    main()