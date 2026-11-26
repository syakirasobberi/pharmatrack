# 💊 PharmaTrack

A Laravel-Based Web Application for Managing and Monitoring Patient Health

---

## 📌 Project Description

PharmaTrack is a web-based system developed to assist pharmacies in managing patient health records efficiently. The system helps pharmacists monitor patient conditions, track medication history, and improve overall healthcare workflow.

---

## 🎯 Objectives

* To manage patient health records digitally
* To monitor patient medication and treatment
* To improve efficiency in pharmacy operations

---

## ⚙️ Features

* 👤 Patient Management
* 💊 Medication Tracking
* 📊 Health Monitoring Dashboard
* 🗂 Record Management

---

## 🛠️ Technologies Used

* Laravel (PHP Framework)
* MySQL Database
* HTML, CSS, JavaScript

---

## 🚀 Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Run `php artisan key:generate`
5. Run `php artisan serve`

---

## Staff two-step sign-in

Admins and pharmacists enter a 6-digit code emailed to them after their password (valid
10 minutes, 5 tries). "Remember this device" skips it for 30 days on that browser; changing
the password cancels every remembered device. Patients and the kiosk are not affected.

Emergency switch: if email stops working and staff are locked out, add
`TWO_FACTOR_ENABLED=false` to the server `.env` (via FTP). Remove it again once email works.

## Running the tests

Automated tests cover sign-in and role access, every page opening for each role, check-ups,
medicines/doses and patient management. They run against a separate MySQL database, never
the real `pharmatrack` one.

One-time setup (Laragon MySQL running):

```
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS pharmatrack_test"
composer install        # installs PHPUnit locally (dev packages)
```

Run them before every deploy:

```
php artisan test
```

The tests refuse to run if the database is not `pharmatrack_test` (for example after
`php artisan config:cache`; run `php artisan config:clear`).

Deploy note: after `composer install`, local `vendor/` contains dev packages. Never upload
`vendor/` to the server without running `composer install --no-dev` first.


## 👩‍💻 Author

Nurul Syakirah Sobberi
Bachelor of Software Engineering
