<div align="center">
  <img src="readme_assets/RuangKita_Logo6.png" alt="RuangKita Logo" width="280">

  # RuangKita

  **A web-based platform for managing student aspirations, feedback, and organizational responses.**
</div>

RuangKita provides students with a structured and transparent channel to submit
aspirations, complaints, and suggestions. Each submission can be moderated,
prioritized through votes, discussed through comments, assigned for follow-up,
and tracked until it is resolved.

## Table of Contents

1. [Features](#features)
2. [Actors](#actors)
3. [DFD and System Flow](#dfd-and-system-flow)
4. [Folder Structure](#folder-structure)
5. [Tech Stack](#tech-stack)
6. [Database Management System](#database-management-system)
7. [Contributors and Role Division](#contributors-and-role-division)

## Features

- Authentication for students, administrators, and departments.
- Student registration and profile management.
- Submission of categorized aspirations with optional images or videos.
- Anonymous posting option.
- Moderation workflow for approving or rejecting new submissions.
- Public feed with search, category filters, pagination, and sorting by newest,
  popularity, or unresolved status.
- Upvote and downvote system with one vote per student on each post.
- Comment section for discussion and clarification.
- Status tracking: `Not Reviewed`, `In Process`, `Communicated`, `Resolved`,
  and `Rejected`.
- Notifications for status changes, official responses, and new comments.
- Administrative dashboard with statistics, filters, moderation controls,
  priority indicators, and CSV export.
- Department dashboard for monitoring and updating approved aspirations.
- Status history through an audit log.

## Actors

| Actor | Responsibilities |
|---|---|
| **Student** | Registers and logs in, manages a profile, submits an aspiration, posts anonymously when needed, views the feed, votes, comments, receives notifications, and tracks progress. |
| **Admin / BEM** | Reviews pending submissions, accepts or rejects posts, assigns or forwards approved aspirations, provides official responses, updates statuses, monitors statistics, and exports aspiration data. |
| **Department** | Views aspirations that have passed moderation, follows up on assigned or approved issues, adds notes, and updates progress to `In Process`, `Communicated`, or `Resolved`. |

## DFD and System Flow

<div align="center">
  <img src="readme_assets/Level1_dfd.png" alt="RuangKita moderation data flow diagram" width="100%">
</div>

The supplied DFD describes the moderation process and the interaction between
the **Admin**, post data, category data, department data, and notification data.

1. **View pending posts**  
   The admin opens the moderation page. The system reads pending submissions
   from the post data store and displays the post list.

2. **Review a post**  
   The admin selects a submission. The system retrieves its complete details
   and reads the relevant category information to support the review.

3. **Moderate the post**  
   The admin decides whether the aspiration should be accepted or rejected.
   The result updates the moderation status in the post data store.

4. **Assign an accepted post**  
   For an accepted aspiration, the admin reads the available department data
   and selects the responsible department. The assignment is recorded and a
   notification is created.

5. **Department follow-up**  
   The department views approved aspirations and updates their progress. Each
   change updates the post status and is recorded in the status log.

6. **Student tracking**  
   Approved posts become visible in the student feed. Students can vote,
   comment, and monitor updates until the aspiration is resolved. Relevant
   activities generate notifications for the post owner.

## Folder Structure

```text
RuangKita/
|-- App/
|   |-- admin/
|   |   |-- components/       # Reusable admin dashboard UI
|   |   |-- includes/         # Admin authentication and helpers
|   |   `-- queries/          # Admin database operations
|   |-- api/                  # Post, vote, status, profile, and notification APIs
|   |-- assets/
|   |   |-- admin/            # Admin-specific CSS and JavaScript
|   |   |-- CSS/              # Application stylesheets
|   |   `-- JS/               # Frontend scripts and API client
|   |-- auth/                 # Login, signup, logout, and department dashboard
|   |-- config/               # Database configuration
|   |-- image/                # Logos, UI images, and uploaded post media
|   |-- students/             # Student feed, profile, and create-post pages
|   `-- uploads/avatars/      # Uploaded profile pictures
|-- database/
|   `-- ruangkita.sql         # Database schema and initial accounts
|-- readme_assets/
|   |-- Level1_dfd.png        # Moderation data flow diagram
|   `-- RuangKita_Logo6.png   # README logo
|-- palette.txt               # Project color palette
`-- README.md
```

## Tech Stack

<div align="center">

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)
![Figma](https://img.shields.io/badge/Figma-F24E1E?style=for-the-badge&logo=figma&logoColor=white)
![Postman](https://img.shields.io/badge/Postman-FF6C37?style=for-the-badge&logo=postman&logoColor=white)

</div>

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 8 and REST-style endpoints |
| Database | MySQL with SQL |
| Local Server | Apache through XAMPP |
| Database Access | PHP MySQLi with prepared statements |
| UI/UX Design | Figma |
| API Testing | Postman |

## Database Management System

RuangKita uses **MySQL** as its relational DBMS. The connection is handled by
PHP through **MySQLi**, while the database definition is available in
[`database/ruangkita.sql`](database/ruangkita.sql).

| Table | Purpose |
|---|---|
| `students` | Stores student accounts, NIM, email, password, and profile picture. |
| `admins` | Stores administrator or BEM accounts. |
| `departments` | Stores organizational department accounts. |
| `posts` | Stores aspiration content, category, attachment, anonymity flag, and current status. |
| `votes` | Stores one upvote or downvote per student for each post. |
| `comments` | Stores student discussions on aspirations. |
| `post_status_logs` | Records every status change, actor, note, and timestamp. |
| `admin_responses` | Stores official administrator responses. |
| `notifications` | Stores comment, response, and status-change notifications for students. |

Main relationships:

- One student can create many posts.
- One post can have many votes, comments, status logs, responses, and
  notifications.
- A student can only have one active vote on a post.
- Deleting a student or post cascades to related records where foreign keys
  are defined.

## Contributors and Role Division

| Contributor | Student ID | Role | Main Responsibilities |
|---|---|---|---|
| **Kanaya Salsabila Humaira** | F1D02410061 | Project Manager and Database Integration | Project planning, task distribution, timeline and progress monitoring, team coordination, database schema and relationships, frontend-backend integration, testing, feature validation, and documentation. |
| **Abdurrahman Karim** | F1D02410031 | Frontend and UI Developer | Figma slicing, authentication UI, student feed, create-post page, responsive layouts, design consistency, frontend integration, user experience, and cross-device compatibility. |
| **Septania Sybil Shofiyah** | F1D02410094 | Backend and API Developer | Authentication, post CRUD, voting, comments, notifications, status APIs, database connectivity, business logic, server-side validation, API testing, security, and data communication. |
