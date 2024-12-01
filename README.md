# API Documentation for User Authentication

## Table of Contents

-   [API Endpoints](#api-endpoints)
    -   [Register User + Send OTP](#register-user--send-otp)
    -   [Check OTP](#check-otp)
    -   [Finish OTP](#finish-otp)
    -   [Resend OTP](#resend-otp)
    -   [Login](#login)

    -   [Request Reset Password + Send OTP]
    -   [Request OTP]
    -   [Check OTP]
    -   [Reset Password]

    -   [Get Profile]
    -   [Update Profile]

    -   [Migration & Model Slider]
    -   [Seeder Slider]
    -   [API Slider]

    -   [Migration & Model Category]
    -   [Seeder Category]
    -   [API Category]
-   [Error Handling](#error-handling)
-   [Sample Requests](#sample-requests)

---

## API Endpoints

### Register User + Send OTP

**Endpoint:** `POST /api/register`  
**Description:** Register a new user and send an OTP to the provided email.

#### Request Body:

| Parameter             | Type     | Description             | Required |
| --------------------- | -------- | ----------------------- | -------- |
| name                  | `string` | Full name of the user   | Yes      |
| email                 | `string` | User's email address    | Yes      |
| password              | `string` | Password (min. 8 chars) | Yes      |
| password_confirmation | `string` | Confirm password        | Yes      |

#### Response:

-   **201 Created**  
    OTP successfully sent to the email.

```json
{
    "status": "success",
    "message": "User registered successfully. OTP sent to email."
}
```
