# 🧩 Dynamic Form Builder & Analyzer

[![Live](https://img.shields.io/badge/demo-live-green?style=flat-square)](https://formmgmt.azurewebsites.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7%2B-black?style=flat-square&logo=symfony)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue?style=flat-square&logo=php)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue?style=flat-square&logo=postgresql)](https://www.postgresql.org/)
[![License](https://img.shields.io/badge/license-MIT-lightgrey?style=flat-square)](LICENSE)

A powerful and responsive web app to **build, manage, and analyze custom forms** — such as quizzes, surveys, and polls. Think of it as an advanced alternative to Google Forms, with deep customization, user roles, and integration capabilities.

---

## 🌐 Live Demo

🔗 **[https://formmgmt.azurewebsites.net](https://formmgmt.azurewebsites.net)**

---

## ✨ Features

- 💡 **Drag-and-Drop Form Builder**  
  Add multiple question types: text, number, checkbox (more coming soon).

- 🔒 **Role-Based Access**  
  Roles for Users, Authors, Admins — includes **admin impersonation**.

- 📊 **Live Results & Stats**  
  View real-time response data with summaries, averages, and frequent answers.

- 🧠 **PostgreSQL Full-Text Search**  
  Search templates instantly using advanced indexing.

- 🌐 **Multi-language UI**  
  English 🇺🇸 and Bangla 🇧🇩 with **dark/light theme switching**.

- 🖼️ **Cloud Uploads**  
  Upload images via drag-and-drop. Tag suggestions with autocomplete.

- 💬 **Live Comments**  
  Real-time comment updates and one-like-per-user enforcement.

- 📱 **Responsive UI**  
  Mobile-first design using Bootstrap, with modern UX patterns.

- 🛠️ **Admin Panel**  
  Full user control, including self-right revocation, public/private template management.

- 📚 **Access Control on Templates**  
  Public/private visibility with autocomplete for user/email invitations.

---

## 🔌 Integrations

### 🔄 Salesforce (CRM Sync)
- Adds a **"CRM Sync"** button in user/admin profiles.
- Fetches CRM data and creates an **Account + Contact** via Salesforce REST API.

### ⚙️ Microsoft Power Automate (Support Tickets)
- Users can submit issues from any page.
- Generates a **JSON file** with summary + metadata.
- Uploads to Dropbox.
- Power Automate flow triggers:
  - Sends email to admins
  - Pushes notification to Power Automate mobile app

---

## 🧱 Tech Stack

- **Backend**: Symfony 7+, PHP 8.2+
- **Frontend**: Bootstrap 5, jQuery, Vanilla JS
- **Database**: PostgreSQL
- **Tools & Libs**:
  - Markdown parser
  - Dropzone (file uploads)
  - jQuery UI Ui (tagging)
  - Custom components

---
