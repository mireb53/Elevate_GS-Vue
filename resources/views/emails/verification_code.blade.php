<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verification Code</title>
    <style>
      body { font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color: #222; }
      .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; max-width: 480px; margin: 0 auto; }
      .code { font-size: 24px; font-weight: bold; letter-spacing: 4px; background: #f3f4f6; padding: 8px 12px; border-radius: 6px; display: inline-block; }
      .muted { color: #6b7280; font-size: 12px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>ElevateGS Email Verification</h2>
      <p>Use the following code to verify your email address:</p>
      <p class="code">{{ $code }}</p>
      <p class="muted">This code will expire in 10 minutes. If you did not request this, you can ignore this email.</p>
    </div>
  </body>
  </html>
