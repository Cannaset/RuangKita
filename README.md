# RuangKita
RuangKita is a digital aspiration platform designed to provide students with a structured and transparent way to submit feedback, complaints, and suggestions. The platform enables users to post text-based reports with optional image attachments, allowing issues such as facility damage, academic concerns, or administrative problems to be clearly documented.

All submissions are displayed in a vertically scrollable feed, similar to social media platforms, making information accessible, organized, and easy to monitor.

The system is designed not merely as a complaint board, but as a traceable workflow that ensures every aspiration can be followed through to completion.

# Role Division
- Kanaya Salsabila Humaira (F1D02410061) (Project Manager)

  Responsible for project planning and team coordination, Database integration, and Documentation (Database schema, table relation, FE-BE integration, testing, documentation). Also responsible for task distribution, project timeline management, progress monitoring, team communication, feature validation, and ensuring the overall development process runs efficiently and according to project objectives.
- Abdurrahman Karim (F1D02410031)
  
  Responsible for Frontend and UI development (Figma slicing, login page, feed page, create post page, responsive layout), including implementing user interfaces, maintaining design consistency, optimizing user experience, handling frontend integration with backend services, and ensuring cross-device compatibility.
- Septania Sybil Shofiyah (F1D02410094)

  Responsible for Backend and API development (Authentication, CRUD post, vote system, status update API), including database connectivity, endpoint development, business logic implementation, API testing, server-side validation, and ensuring secure and efficient data communication between frontend and backend systems.

# Key Features
- User Authentication (Student Login)
- Create Post (text with optional image attachment)
- Upvote/Downvote System to prioritize issues
- Comment Section for discussion and clarification
- Shareable Post Links
- Status Tracking System, including:
  - Not Reviewed
  - In Process
  - Communicated
  - Resolved
Issues with higher vote support are automatically prioritized for follow-up.

# User Roles
```bash
Users
├── Student
│   ├── Sign Up
│   ├── Log In
│   │  ├── Profile
│   │  │  ├── Change Email
│   │  │  ├── Change Username
│   │  │  └── Change Profile Picture
│   │  ├── Post
│   │  ├── Comments
│   │  └── Votes
│   └── Log Out
│
├── Admin
│   ├── Log In
│   │  ├── Profile
│   │  │  ├── Change Email
│   │  │  ├── Change Username
│   │  │  └── Change Profile Picture
│   │  ├── Post
│   │  ├── Moderating Post
│   │  │  ├── Accepting Post
│   │  │  └── Rejecting Post
│   │  └── Aspirations Update
│   │     ├── Proceed
│   │     ├── Done
│   │     └── Rejected (By Department)
│   └── Log Out
│
└── Department
    ├── Log In
    │  ├── Profile
    │  │  ├── Change Email
    │  │  ├── Change Username
    │  │  └── Change Profile Picture
    │  ├── Saw Post
    │  └── Aspirations Update
    │     ├── Proceed
    │     ├── Done
    │     └── Rejected (By Department)
    └── Log Out
 

```

# Objective
RuangKita aims to create an aspiration management system that is:
- Transparent
- Organized
- Trackable
- Accountable

# Tech Stack
- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Design: Figma
- API Testing: Postman

# Database Management System (DBMS)

| Component | Technology |
|---|---|
| Database Management System | MySQL |
| Backend Integration | PHP |
| Query Language | SQL |
| Database Access | MySQL Driver / ORM |
| API Testing | Postman |
| Database Design Tool | Draw.io / Figma / ERD Tool |
| Hosting Compatibility | XAMPP / Laragon / cPanel / VPS |

This platform ensures that submitted aspirations are not overlooked, but properly monitored and resolved through a structured process.
