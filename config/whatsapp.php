<?php
/**
 * WhatsApp API & Automated Notifications Configuration
 * YMCA Badminton Club ERP
 * 
 * Edit your WhatsApp API credentials, settings, and templates directly in this file.
 */

return [

    // --------------------------------------------------------------------------
    // 1. Gateway Provider
    // --------------------------------------------------------------------------
    // Options:
    //   'meta'    - Official Meta WhatsApp Cloud API (Graph API v19.0+) [RECOMMENDED]
    //   'gateway' - Generic REST API Gateway (UltraMsg / Wablas / Wati / Wassenger)
    //   'wame'    - Direct WhatsApp Web / App Links (wa.me - no API key needed)
    'provider' => 'meta',

    // --------------------------------------------------------------------------
    // 2. Master Toggles
    // --------------------------------------------------------------------------
    // Master switch to enable or disable all WhatsApp notifications (1 = Active, 0 = Disabled)
    'is_active' => 1,

    // Automatically send receipt when fee payment is recorded (1 = Yes, 0 = No)
    'auto_send_payment' => 1,

    // Attach official generated receipt PDF as a WhatsApp document (1 = Yes, 0 = No)
    'send_pdf_receipt' => 1,

    // --------------------------------------------------------------------------
    // 3. Meta WhatsApp Cloud API Credentials (https://developers.facebook.com)
    // --------------------------------------------------------------------------
    // Your WhatsApp Phone Number ID (From Meta Developer Portal -> WhatsApp -> API Setup)
    'phone_number_id' => '1201793359690573', // e.g. '109823485729182'

    // Permanent Access Token (From Meta Business Manager -> System Users)
    'api_token' => 'EAAVXZCA0n0WIBSGTzawG7n3jIXIBZCDjz37zXNVhdhN3AuTujUnWweyZBVocz1O0NKIf4wvbygZCIXgoMO1vE1T516aMynUhYm6mEZBZCTb56CmsCu4S2eHORfqtifGGBvEmgxyc52E19k9SdjP1iOQh8WYsN4CVKfcZBIVMP4n5jaqTkAccnZAef5RVdDa97Eg0gQZDZD', // e.g. 'EAAG...'

    // WhatsApp Business Account ID (WABA ID)
    'business_account_id' => '', // e.g. '293847291048293'

    // Optional: Approved Meta Template Names (for sending 24/7 outside customer care window)
    // In Meta WhatsApp Manager -> Message Templates (Category: UTILITY)
    'meta_template_payment' => 'fee_payment_receipt',   // Header: Document (PDF)
    'meta_template_balance' => 'fee_balance_reminder',  // Header: None
    'meta_template_lang'    => 'en',                    // Language Code ('en' or 'en_US')

    // --------------------------------------------------------------------------
    // 4. Generic REST Gateway / UltraMsg Credentials (Optional)
    // --------------------------------------------------------------------------
    // API URL if using a third-party gateway (leave empty if using Meta Cloud API)
    'api_url' => '', // e.g. 'https://api.ultramsg.com/instanceXXXX/messages/chat'

    // Club's registered WhatsApp phone number (in international format without +)
    'sender_phone' => '', // e.g. '919495183688'

    // --------------------------------------------------------------------------
    // 5. Club Information & UPI Details
    // --------------------------------------------------------------------------
    'club_name'  => defined('CLUB_NAME') ? CLUB_NAME : 'YMCA BCP Poovathussery',
    'upi_id'     => 'ymcabcp@okaxis',
    'payee_name' => 'YMCA BCP Poovathussery',

    // --------------------------------------------------------------------------
    // 6. Message Templates
    // --------------------------------------------------------------------------
    // Available Placeholder Tags:
    //   {member_name}   - Full name of the member
    //   {amount}        - Amount paid
    //   {receipt_no}    - Official Receipt Number
    //   {date}          - Payment date (DD-MM-YYYY)
    //   {payment_mode}  - Mode of payment (Cash, Bank/UPI, Wallet)
    //   {balance}       - Current remaining balance due
    //   {club_name}     - Name of the club
    //   {upi_id}        - UPI ID for payments
    //   {payee_name}    - Payee account name

    // A. Payment Receipt Confirmation (Sent with attached PDF receipt, text is the caption)
    'template_payment' => "Dear *{member_name}*, your fee payment for *{club_name}* is confirmed!\n\nReceipt Number: *#{receipt_no}*\nAmount Received: *₹{amount}*\nPayment Date: *{date}*\nPayment Mode: *{payment_mode}*\nCurrent Balance Due: *₹{balance}*\n\nYour official Payment Receipt PDF has been attached to this message.\n\nThank you for your timely payment and support!",

    // B. Pending Balance Alert (Option 2 - Sent via 'Send Alert' button)
    'template_balance' => "🏸 *{club_name}*\n━━━━━━━━━━━━━━━━━━━━\n*MEMBERSHIP DUES REMINDER*\n\nDear *{member_name}*,\n\nWe would like to remind you that your monthly membership dues have a pending balance:\n\n💰 *Amount Due:* *₹{balance}*\n📲 *UPI ID:* {upi_id} ({payee_name})\n\nPlease clear the dues to ensure uninterrupted access to club facilities.\n\nThank you for your prompt payment!\n━━━━━━━━━━━━━━━━━━━━",

];
