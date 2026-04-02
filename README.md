# 🛡️ Secure Network Monitoring Dashboard

A secure, database-driven network monitoring system that tracks the availability and health of approved devices through a private admin dashboard.

---

## 📌 Overview

This project is a **full-stack network monitoring system** designed to simulate real-world monitoring environments in a controlled and secure way.

It combines:

* **PHP web dashboard** (admin interface)
* **Python backend collector** (network checks)
* **MySQL database** (data storage)

The system monitors a defined list of devices and records their status over time, allowing administrators to view network health, detect outages, and review historical logs.

---

## ⚙️ Key Features

### 🔐 Authentication & Security

* Secure admin login system
* Password hashing (`password_hash`, `password_verify`)
* Session-based authentication
* Session timeout handling
* CSRF protection on all forms
* SQL injection prevention (prepared statements)
* XSS protection via output escaping

---

### 📊 Dashboard

* Total number of monitored devices
* Online / Offline device counts
* System health indicator
* Latest check timestamp
* Recent monitoring activity feed

---

### 🖥️ Device Management

* Add new devices
* Edit existing devices
* Enable / disable monitoring
* Delete devices safely
* Store device metadata (name, IP, port, type, notes)

---

### 📡 Monitoring System (Backend Collector)

* Python-based collector script
* Runs automatically via Task Scheduler
* Performs:

  * TCP connectivity checks
  * Latency measurement
* Writes results to database

---

### 📜 Logs & History

* View recent logs on dashboard
* Dedicated logs page
* Device-specific history view
* Clear logs functionality

---

## 🏗️ System Architecture

```
           +----------------------+
           |   Admin Dashboard    |
           |      (PHP)           |
           +----------+-----------+
                      |
                      v
           +----------------------+
           |      MySQL DB        |
           |  devices + logs      |
           +----------+-----------+
                      ^
                      |
           +----------+-----------+
           |   Python Collector   |
           | (runs every minute)  |
           +----------------------+
```

---

## 🗄️ Database Structure

### `admins`

Stores admin login credentials

* id
* name
* email
* password
* created_at

---

### `devices`

Stores approved monitored devices

* id
* device_name
* ip_address
* port
* device_type
* notes
* is_active
* created_at

---

### `status_logs`

Stores monitoring results

* id
* device_id
* status (online/offline)
* latency_ms
* message
* checked_at

---

## 🧪 Simulated Network Setup

This project uses a **local simulated network** for testing:

Example devices:

| Device Name        | IP        | Port  | Description       |
| ------------------ | --------- | ----- | ----------------- |
| Local Apache       | 127.0.0.1 | 80    | Web server        |
| Python HTTP Server | 127.0.0.1 | 5000  | Test service      |
| Backup Node        | 127.0.0.1 | 8000  | Secondary service |
| Offline Test Host  | 127.0.0.1 | 65000 | Simulated failure |

---
## 🔒 Security Design Decisions

This system is designed to be **safe by default**:

* ❌ No user-triggered network scanning
* ✅ Only approved devices are monitored
* ✅ Monitoring handled by backend service
* ✅ Admin-only access to sensitive data

This prevents misuse such as:

* Arbitrary IP scanning
* SSRF-style abuse
* Unauthorized network probing

---

## 🎯 Project Goals

This project demonstrates:

* Network monitoring fundamentals
* Secure backend/frontend separation
* Full CRUD web application development
* Database design and usage
* Secure coding practices
* Real-world system architecture

---

## 📈 Possible Improvements (Stretch Goals)

* Uptime percentage tracking
* Charts / visual analytics
* Email alerts for downtime
* SNMP monitoring
* Docker-based deployment
* Role-based access control
* Audit logs

---

## 🧠 What I Learned

* How to safely design a monitoring system
* Importance of separating frontend from network operations
* Implementing real-world web security practices
* Building a system that behaves like production tools

---

## 📷 Screenshots (Optional)

<img width="1218" height="899" alt="image" src="https://github.com/user-attachments/assets/64dd67ce-8562-4c68-a28f-91e20e5753b6" />

---

## 🏁 Status

✅ Core system complete
🔧 UI polish ongoing

---

## 📎 Project Title

**Secure Network Monitoring Dashboard**

---

And honestly? You’re sitting on a *very* solid portfolio piece now.
