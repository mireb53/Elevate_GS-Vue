# Google OAuth 403 Error Fix

## Problem
Error: "The given origin is not allowed for the given client ID"

This occurs when your application's URL (origin) is not authorized in Google Cloud Console.

## Solutions Applied

### 1. Updated LoginView.vue
- Changed from hardcoded client ID to environment variable
- Now reads from `VITE_GOOGLE_CLIENT_ID` in `.env` file

### 2. Added Environment Variable
Added to `.env`:
```
VITE_GOOGLE_CLIENT_ID=651352432780-btgq6d60dl2ga4suk5kn0hce9hhnu9ej.apps.googleusercontent.com
```

## Required: Update Google Cloud Console

**IMPORTANT:** You must add authorized origins in Google Cloud Console:

1. Go to https://console.cloud.google.com/
2. Select your project
3. Navigate to **APIs & Services** → **Credentials**
4. Find OAuth 2.0 Client ID: `651352432780-btgq6d60dl2ga4suk5kn0hce9hhnu9ej`
5. Click to edit it
6. Add these **Authorized JavaScript origins**:
   ```
   http://localhost:8000
   http://127.0.0.1:8000
   http://localhost:5174
   http://127.0.0.1:5174
   ```

7. Add these **Authorized redirect URIs** (if needed):
   ```
   http://localhost:8000/auth/google/callback
   http://127.0.0.1:8000/auth/google/callback
   ```

8. Click **Save**
9. Wait 5-10 minutes for changes to propagate

## Additional Troubleshooting

### Clear Browser Cache
1. Open Developer Tools (F12)
2. Right-click the refresh button
3. Select "Empty Cache and Hard Reload"

### Check Current Origin
Your app is likely running on one of these:
- Laravel: `http://localhost:8000` or `http://127.0.0.1:8000`
- Vite: `http://localhost:5173` or `http://127.0.0.1:5173`

### Test After Setup
1. Restart your development servers:
   ```powershell
   # Terminal 1: Laravel
   php artisan serve
   
   # Terminal 2: Vite
   npm run dev
   ```

2. Clear browser cache
3. Try Google Sign-In again

### Common Issues

**Issue:** Still getting 403 after adding origins
- **Solution:** Wait 5-10 minutes for Google's changes to propagate
- **Solution:** Make sure you saved changes in Google Cloud Console
- **Solution:** Verify you're using the exact same origin (check URL bar)

**Issue:** "Blocked by CORS"
- **Solution:** Add origin to both JavaScript origins AND redirect URIs

**Issue:** Different error message
- **Solution:** Check browser console for specific error details
- **Solution:** Verify client ID matches in both `.env` and Google Cloud Console

## Verify Setup

Check these files have been updated:
- ✅ `.env` - Contains `VITE_GOOGLE_CLIENT_ID`
- ✅ `resources/js/spa/views/LoginView.vue` - Uses `:data-client_id="googleClientId"`

## After Changes

**Always restart dev servers after .env changes:**
```powershell
# Stop current servers (Ctrl+C)
# Then restart:
php artisan serve
npm run dev
```

## Production Deployment

For production, add your production domain:
```
https://yourdomain.com
https://www.yourdomain.com
```

## Contact Support

If issues persist:
- Check Google Cloud Console project permissions
- Verify OAuth consent screen is configured
- Ensure billing is enabled (if required by Google)
