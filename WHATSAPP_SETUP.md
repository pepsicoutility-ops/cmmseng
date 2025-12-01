# WhatsApp Integration with WAHA Cloud - Setup Guide

## Overview
This integration sends automatic WhatsApp notifications to a group when utility checklists (Compressor 1/2, Chiller 1/2, AHU) are submitted via PWA.

## Prerequisites

### 1. WAHA Cloud Service Setup
- Go to https://sumopod.com (as shown in your screenshot)
- Deploy a WAHA Plus Cloud service:
  - **Recommended Plan**: 512 MB RAM (Rp 35,000/month)
  - Good for 2-3 WhatsApp numbers
- After deployment, you'll receive:
  - API URL (e.g., `https://your-instance.waha.so`)
  - API Token (authentication key)

### 2. WhatsApp Business Account
- Use WhatsApp Business or regular WhatsApp
- Create a dedicated group for CMMS notifications
- Get the Group ID (instructions below)

---

## Step-by-Step Setup

### Step 1: Get WhatsApp Group ID

1. **Using WAHA Dashboard** (Easiest):
   - Access your WAHA instance: `https://your-instance.waha.so/dashboard`
   - Login with your credentials
   - Go to "Chats" section
   - Find your group
   - Copy the Chat ID (format: `120363xxxxxxxxxx@g.us`)

2. **Using API** (Alternative):
   ```bash
   curl -X GET "https://your-instance.waha.so/api/chats" \
        -H "Authorization: Bearer YOUR_API_TOKEN"
   ```

### Step 2: Update Laravel Environment

Edit `.env` file:

```env
# WhatsApp WAHA Cloud Configuration
WAHA_API_URL=https://your-instance.waha.so
WAHA_API_TOKEN=your-api-token-from-sumopod
WAHA_SESSION=default
WAHA_GROUP_ID=120363xxxxxxxxxx@g.us
WAHA_ENABLED=true
```

**Important**: Change `WAHA_ENABLED=true` when ready to go live

### Step 3: Test Connection

1. **Via Filament Admin Panel**:
   - Login to admin panel (`/pep`)
   - Go to Settings → WhatsApp
   - Click "Test Connection" button
   - If successful, click "Send Test Message"

2. **Via Tinker** (Manual testing):
   ```bash
   php artisan tinker
   ```
   ```php
   $whatsapp = app(\App\Services\WhatsAppService::class);
   $result = $whatsapp->testConnection();
   print_r($result);
   
   // Send test message
   $whatsapp->sendMessage("Test from CMMS System");
   ```

### Step 4: Verify Integration

1. Submit a test checklist via PWA
2. Check WhatsApp group for notification
3. Check Laravel logs: `storage/logs/laravel.log`

---

## Message Format Examples

### Compressor Checklist
```
🔧 *COMPRESSOR 1 CHECKLIST*

📅 *Shift:* 1
👤 *Operator:* John Doe (81182113)
⏱️ *Submitted:* 29/11/2025 14:30

*📊 Operating Parameters:*
• Total Run Hours: 1000 hrs

*🌡️ Temperature & Pressure:*
• Bearing Oil Temp: 75.5°C
• Bearing Oil Pressure: 30.2 bar
...

✅ Data recorded in CMMS system
```

### Chiller Checklist
```
❄️ *CHILLER 1 CHECKLIST*

📅 *Shift:* 2
👤 *Operator:* Jane Smith (81182114)
⏱️ *Submitted:* 29/11/2025 15:00

*🌡️ Temperature & Pressure:*
• Sat Evap T: 45.8°C
• Sat Dis T: 55.2°C
...

✅ Data recorded in CMMS system
```

### AHU Checklist
```
🌀 *AHU CHECKLIST*

📅 *Shift:* 3
👤 *Operator:* Bob Wilson (81182115)
⏱️ *Submitted:* 29/11/2025 22:00

*🔵 AHU MB-1:*
• MB-1.1: HF=100, PF=50, MF=75
...

✅ Data recorded in CMMS system
```

---

## Configuration Files

### Files Modified/Created:
1. ✅ `.env` - Environment variables
2. ✅ `config/services.php` - WAHA configuration
3. ✅ `app/Services/WhatsAppService.php` - Main service class
4. ✅ `routes/web.php` - Added notifications to submit routes
5. ✅ `app/Filament/Pages/WhatsAppSettings.php` - Admin settings page
6. ✅ `resources/views/filament/pages/whatsapp-settings.blade.php` - Settings view

---

## Features

### ✅ Implemented
- Automatic notifications for all 5 checklists:
  - Compressor 1 & 2
  - Chiller 1 & 2
  - AHU
- Formatted messages with emojis
- Error handling and logging
- Enable/disable via environment variable
- Admin settings page
- Test connection feature
- Test message feature

### 📊 Message Content
Each notification includes:
- Checklist type (with emoji)
- Shift number
- Operator name and GPID
- Submission timestamp
- All measured parameters organized by category
- Optional notes from operator
- Confirmation that data is recorded

---

## Troubleshooting

### Problem: "Connection Failed"
**Solution**:
- Check `WAHA_API_URL` is correct
- Verify `WAHA_API_TOKEN` is valid
- Ensure WAHA service is running
- Check firewall/network access

### Problem: "Group not found"
**Solution**:
- Verify `WAHA_GROUP_ID` format: `120363xxxxxxxxxx@g.us`
- Ensure bot is added to the group
- Check group still exists

### Problem: "Messages not sending"
**Solution**:
- Check `WAHA_ENABLED=true` in `.env`
- Review logs: `storage/logs/laravel.log`
- Test connection via admin panel
- Verify WhatsApp session is active in WAHA

### Problem: "API Rate Limit"
**Solution**:
- WAHA has rate limits per plan
- Consider upgrading to higher plan
- Notifications are queued and sent asynchronously

---

## API Endpoints (WAHA Cloud)

### Send Text Message
```
POST /api/sendText
Headers: Authorization: Bearer YOUR_TOKEN
Body: {
  "session": "default",
  "chatId": "120363xxxxxxxxxx@g.us",
  "text": "Your message"
}
```

### Get Sessions
```
GET /api/sessions
Headers: Authorization: Bearer YOUR_TOKEN
```

### Get Chats
```
GET /api/chats
Headers: Authorization: Bearer YOUR_TOKEN
```

---

## Cost Estimation

### WAHA Cloud Pricing (from screenshot):
- **512 MB RAM**: Rp 35,000/month ⭐ Recommended
- **1 GB RAM**: Rp 60,000/month
- **2 GB RAM**: Rp 100,000/month
- **256 MB RAM**: Rp 25,000/month (Good for 1 WA Number)

### Recommended Plan:
**512 MB RAM** - Sufficient for:
- 2-3 WhatsApp numbers
- Up to 100 messages/day
- Good reliability

---

## Security Notes

1. **Never commit** `.env` to git
2. Keep API token secure
3. Rotate API token periodically
4. Only super_admin and managers can access WhatsApp settings
5. All API calls are logged for audit

---

## Support & Documentation

- **WAHA Docs**: https://waha.devlike.pro/
- **WAHA GitHub**: https://github.com/devlikeapro/waha
- **SumoPod Support**: Check their dashboard

---

## Next Steps After Setup

1. ✅ Deploy WAHA service on SumoPod
2. ✅ Get API credentials
3. ✅ Create WhatsApp group
4. ✅ Update `.env` with credentials
5. ✅ Test connection
6. ✅ Send test message
7. ✅ Set `WAHA_ENABLED=true`
8. ✅ Submit test checklist
9. ✅ Verify notification received
10. ✅ Monitor logs for errors

---

## Maintenance

### Regular Tasks:
- Monitor WAHA service uptime
- Check message delivery logs
- Verify WhatsApp session is active
- Review monthly usage/costs

### Monthly Checklist:
- [ ] WAHA service running smoothly
- [ ] API token still valid
- [ ] WhatsApp group active
- [ ] No errors in logs
- [ ] All team members in group

---

**Status**: ✅ Ready to deploy
**Last Updated**: November 29, 2025
