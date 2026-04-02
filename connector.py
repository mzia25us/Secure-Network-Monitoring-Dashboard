import socket
import time
import mysql.connector

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "network_monitor"
}

def check_tcp(ip, port, timeout=2):
    start_time = time.time()

    try: 
        with socket.create_connection((ip, port), timeout=timeout):
            latency_ms = round((time.time() - start_time) * 1000, 2)
            return "online", latency_ms, "Connection successful."
    except Exception as e:
        return "offline", None, str(e)
    
def main():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        cursor.execute("""
            SELECT id, device_name, ip_address, port
            FROM devices
            WHERE is_active = 1
        """)
        devices = cursor.fetchall()

        if not devices:
            print("No active devices have been found.")
            return
        
        for device in devices: 
            status, latency, message = check_tcp(device["ip_address"], device["port"])

            insert_sql = """
                INSERT INTO status_logs (device_id, status, latency_ms, message)
                VALUES (%s, %s, %s, %s)
            """

            cursor.execute(insert_sql, (
                device["id"],
                status,
                latency,
                message
            ))

            print(
                f"{device['device_name']} ({device['ip_address']}:{device['port']})"
                f"-> {status.upper()} | latency={latency} | message={message}"
            )
        
        conn.commit()

    except mysql.connector.Error as db_error:
        print("Database error:", db_error)

    finally:
        try:
            cursor.close()
            conn.close()
        except: 
            pass

if __name__ == "__main__":
    main()