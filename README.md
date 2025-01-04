# API Documentation for User Authentication

## Table of Contents

-   [API Endpoints](#api-endpoints)
    -   [X] [Register User + Send OTP](#register-user--send-otp)
    -   [X] [Check OTP](#check-otp)
    -   [X] [Finish OTP](#finish-otp)
    -   [X] [Resend OTP](#resend-otp)
    -   [X] [Login](#login)

    -   [X] [Request Reset Password + Send OTP]
    -   [X] [Request OTP]
    -   [X] [Check OTP]
    -   [X] [Reset Password]

    -   [X] [Get Profile]
    -   [X] [Update Profile]

    -   [X] [Migration & Model Slider]
    -   [X] [Seeder Slider]
    -   [X] [API Slider]

    -   [X] [Migration & Model Category]
    -   [X] [Seeder Category]
    -   [X] [API Category]

    -   [X] [Migration and Model Province, City, Address]
    -   [X] [Import Database Dump for Provinces, Cities]
    -   [X] [API CRUD Address]
    -   [X] [API Set Address Utama]

    -   [X] [Create Kredensial google]
    -   [X] [Create Config Google]
    -   [X] [Create API]

    -   [X] [Migration dan Model: Product, Images, Variation, dan Reviews]
    -   [X] [Dummy Files]
    -   [X] [Seeder Product]
    -   [X] [API Explore Product]
    -   [X] [API Detail Product]
    -   [X] [API Product Review]
    -   [X] [API Seller]

    -   [X] [Migration & Model: Cart, and CartItem]
    -   [X] [Add to Cart]
    -   [X] [List Cart]
    -   [X] [Update Cart Items]
    -   [X] [Remove Items]

    -   [x] [Migration & Model: Voucher]
    -   [x] [Voucher Seedeer]
    -   [x] [Get List Voucher]
    -   [x] [Apply Voucher]
    -   [x] [Remove Voucher]

    -   [x] [Overview + Raja Ongkir Configuration]
    -   [x] [Set Address]
    -   [x] [Get Shipping]
    -   [x] [Update Shipping]

    -   [x] [Overview Checkout]
    -   [x] [Overview Midtans]
    -   [x] [API Checkout]
    -   [x] [Callback Midtrans]

    -   [x] [Install Package Wallet]
    -   [x] [Add Dummy Deposito]
    -   [x] [Add balance on profile response]
    -   [x] [API Checkout Toggle Coin]
    -   [] [Cut Balance on Checkout]
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
