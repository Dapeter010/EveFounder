@component('mail::message')
# 👋 Welcome to OddJobber, {{ ucfirst($user->first_name) }}!

---

## 🌟 Congratulations!
You’ve successfully joined **OddJobber**. We’re thrilled to have you onboard as we connect you with the best odd job opportunities and skilled professionals to meet your needs.

### 🚀 Your OddJobber Journey Starts Here
Here’s what we offer to make your experience seamless:

@component('mail::panel')
### 🛠️ What You Get:
- **Access to a Wide Network:** Connect with trusted professionals or clients across various services.
- **Real-Time Job Updates:** Stay informed about the latest job postings or applicant progress.
- **Secure Payments:** Enjoy a safe and hassle-free payment system for all transactions.
@endcomponent

---

## 🔐 Your Default PIN:
To get started, your default transaction pin is: **{{ $pin }}**

> Please ensure you update your PIN after logging in to keep your account secure.

---

## ✅ Validate Your Email
Activate your account within **24 hours** to ensure uninterrupted access by clicking the button below:

@component('mail::button', ['url' => $verification, 'color' => 'success'])
Activate Your Account
@endcomponent

---

Thank you for choosing **OddJobber**! We’re here to help you accomplish more with ease.
If you need assistance, feel free to contact our support team anytime.

Warm regards,
**The OddJobber Team**
@endcomponent
