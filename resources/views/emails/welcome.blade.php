@component('mail::message')
# Welcome to EveFound, {{ ucfirst($user->first_name) }}!

---

We're absolutely delighted to have you join the EveFound community! We're personally invested in helping you find meaningful connections and, ultimately, the love you deserve.

## Your Journey to Love Starts Here

EveFound is more than just a dating platform – it's a carefully crafted space designed to help UK singles like you find genuine, lasting relationships. We're here to support you every step of the way.

@component('mail::panel')
### Getting Started - Your Path to Success:

**Complete Your Profile:**
A complete, authentic profile is your first step to finding compatible matches. Let your personality shine through!

**Discover Your Matches:**
Our intelligent matching system connects you with people who share your values and interests.

**Start Meaningful Conversations:**
When you match with someone special, don't hesitate – send that first message!
@endcomponent

---

## Boost Your Success

Want to increase your chances of finding that special someone? Here are some premium features to consider:

**Profile Boosts** – Get more visibility and stand out to potential matches in your area. Perfect for when you want to make a great first impression!

**Premium Subscription** – Unlock advanced features including unlimited likes, see who's liked you, and get priority visibility in searches. Our premium members find matches faster!

@component('mail::button', ['url' => config('app.frontend_url') . '/subscription', 'color' => 'primary'])
Explore Premium Features
@endcomponent

---

## We're Here for You

Your success in finding love matters to us personally. We're constantly working to create the best experience for our community.

If you have any questions, suggestions, or just want to share your experience, we'd love to hear from you at **contactus@evefound.com**

Here's to finding your perfect match!

Warmest regards,
**The EveFound Team**

---

*P.S. Don't forget to add your best photos and fill out your profile completely – it makes all the difference!*
@endcomponent
