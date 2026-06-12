# Olkoo Payment OS - Installation Guide

This guide will walk you through installing and configuring the Olkoo Payment OS plugin for WooCommerce.

## Prerequisites

Before installing, ensure your system meets these requirements:

- **WordPress**: Version 5.8 or higher
- **WooCommerce**: Version 5.0 or higher
- **PHP**: Version 7.4 or higher
- **SSL Certificate**: HTTPS enabled (required for production webhooks)

## Installation Methods

### Method 1: WordPress Admin Panel (Recommended)

1. Download the plugin zip file from the releases page
2. Log in to your WordPress admin panel
3. Navigate to **Plugins → Add New**
4. Click **Upload Plugin** button
5. Choose the downloaded zip file
6. Click **Install Now**
7. After installation, click **Activate Plugin**

### Method 2: FTP Upload

1. Download and extract the plugin zip file
2. Connect to your server via FTP
3. Upload the `olkoo-payment-os` folder to `/wp-content/plugins/`
4. Log in to WordPress admin
5. Navigate to **Plugins**
6. Find **Olkoo Payment OS** and click **Activate**

### Method 3: Git Clone (For Developers)

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/okenlysolutions/olkoo-payment-os.git
```

Then activate via WordPress admin → Plugins.

## Initial Configuration

### Step 1: Enable WooCommerce

Ensure WooCommerce is installed and activated:

1. Go to **Plugins**
2. Verify **WooCommerce** is active
3. If not installed, install from WordPress.org

### Step 2: Access Payment Settings

1. Navigate to **WooCommerce → Settings**
2. Click the **Payments** tab
3. You should see **TaraMoney** in the list of payment methods

## TaraMoney Gateway Configuration

### Step 3: Enable TaraMoney Gateway

1. In the Payments tab, find **TaraMoney**
2. Toggle the switch to enable it
3. Click **Manage** to configure settings

### Step 4: Configure API Credentials

#### Get Your TaraMoney API Credentials

1. Log in to [TaraMoney Dashboard](https://www.dklo.co/dashboard)
2. Navigate to API settings
3. Copy your:
   - API Key
   - Business ID
   - Webhook Secret

#### Enter Production Credentials

In the TaraMoney settings page:

1. **Enable/Disable**: Check to enable the gateway
2. **Title**: "TaraMoney" (or customize for customers)
3. **Description**: Customize the payment method description
4. **Live API Key**: Paste your production API key
5. **Live Business ID**: Paste your production business ID
6. **Webhook Secret**: Paste your webhook secret

#### Configure Test Mode (Optional)

For testing before going live:

1. Check **Enable Test Mode**
2. **Test API Key**: Paste your sandbox API key
3. **Test Business ID**: Paste your sandbox business ID

### Step 5: Configure Payment Options

Choose which payment methods to enable:

- **Enable Order Links**: ✓ (WhatsApp, Telegram, SMS)
- **Enable Mobile Money**: ✓ (Orange Money, MTN Mobile Money)

### Step 6: Configure Webhook

#### Copy Your Webhook URL

The webhook URL is displayed in the settings:

```
https://yourdomain.com/?wc-api=olkoo_webhook_taramoney
```

#### Add Webhook to TaraMoney Dashboard

1. Log in to [TaraMoney Dashboard](https://www.dklo.co/dashboard)
2. Navigate to Webhook Settings
3. Add your webhook URL
4. Save the configuration

**Important Notes:**
- Webhook URL must use HTTPS in production
- For local development, you may need to use a tunneling service like ngrok

### Step 7: Save Settings

Click **Save changes** at the bottom of the page.

## Verification

### Test the Installation

1. Go to your WooCommerce shop
2. Add a product to cart
3. Proceed to checkout
4. Verify **TaraMoney** appears as a payment option

### Test Payment Flow

#### Test Order Link Payment

1. Complete checkout with TaraMoney selected
2. You should be redirected to payment page
3. Choose payment method (WhatsApp/Telegram/SMS)
4. Complete payment
5. Verify order status updates in admin

#### Test Mobile Money Payment

1. At checkout, enter a valid mobile number
2. Complete checkout
3. You'll receive a USSD code
4. Dial the code on your mobile phone
5. Complete payment
6. Verify order status updates

## Logging Configuration

### Enable Detailed Logging

For debugging and monitoring:

1. Go to **WooCommerce → Settings → Payments**
2. Scroll to **Olkoo Payment OS Settings**
3. Check **Enable Logging**
4. Set **Log Level** to desired level:
   - **Debug**: Most detailed (recommended for development)
   - **Info**: Normal operations (recommended for production)
   - **Warning**: Only warnings and errors
   - **Error**: Only errors

### View Logs

Access logs at: **WooCommerce → Status → Logs**

Look for logs with source: `olkoo-payment-os-taramoney`

## Troubleshooting Installation

### Plugin Not Appearing

**Issue**: Plugin doesn't show in plugins list

**Solutions**:
- Verify the plugin folder is in `/wp-content/plugins/`
- Check folder name is exactly `olkoo-payment-os`
- Ensure all files were uploaded correctly
- Check file permissions (755 for directories, 644 for files)

### WooCommerce Missing Notice

**Issue**: Warning that WooCommerce is required

**Solution**:
1. Install WooCommerce from **Plugins → Add New**
2. Activate WooCommerce
3. Complete WooCommerce setup wizard
4. Retry activating Olkoo Payment OS

### Gateway Not Showing at Checkout

**Issue**: TaraMoney doesn't appear as payment option

**Solutions**:
1. Verify gateway is enabled in settings
2. Check API credentials are configured
3. Ensure WooCommerce is up to date
4. Clear site cache if using caching plugin
5. Check for plugin conflicts

### Webhook Not Receiving Payments

**Issue**: Order status not updating after payment

**Solutions**:
1. Verify webhook URL uses HTTPS
2. Check webhook URL is correctly configured in TaraMoney dashboard
3. Ensure webhook secret matches
4. Check server firewall isn't blocking requests
5. Review logs for webhook errors

## Local Development Setup

### Using ngrok for Webhook Testing

Since webhooks require HTTPS, use ngrok for local testing:

1. Install ngrok: https://ngrok.com/download
2. Start your local WordPress:
   ```bash
   # If using XAMPP/MAMP, start Apache and MySQL
   ```

3. Start ngrok tunnel:
   ```bash
   ngrok http 80
   ```

4. Copy the HTTPS URL provided by ngrok
5. Update your WordPress site URL temporarily
6. Use the ngrok URL for webhook configuration

### Local Test Mode

Enable test mode for local development:

1. Use TaraMoney sandbox credentials
2. Enable **Test Mode** in plugin settings
3. Test with sandbox payment methods

## Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] WooCommerce is active and configured
- [ ] TaraMoney gateway enabled
- [ ] API credentials configured (live or test)
- [ ] Webhook URL added to TaraMoney dashboard
- [ ] Payment options selected
- [ ] Test payment completed successfully
- [ ] Webhook receiving payment notifications
- [ ] Order status updating correctly
- [ ] Logging enabled and reviewed

## Next Steps

After successful installation:

1. **Test Thoroughly**: Complete multiple test transactions
2. **Configure Notifications**: Set up order confirmation emails
3. **Train Staff**: Ensure your team knows how to handle TaraMoney orders
4. **Monitor Logs**: Regularly check logs for any issues
5. **Go Live**: Switch from test mode to production when ready

## Getting Help

If you encounter issues during installation:

- **Documentation**: https://okenlysolutions.com/docs/olkoo-payment-os
- **Support Email**: support@okenlysolutions.com
- **GitHub Issues**: https://github.com/okenlysolutions/olkoo-payment-os/issues

## Security Recommendations

After installation:

1. **Keep Updated**: Regularly update WordPress, WooCommerce, and the plugin
2. **Use HTTPS**: Always use SSL certificate for production
3. **Strong Credentials**: Use strong API keys and webhook secrets
4. **Regular Backups**: Backup your site regularly
5. **Monitor Logs**: Review logs for suspicious activity
6. **Limit Access**: Restrict admin access to trusted users only

---

**Installation complete! You're ready to accept payments with Olkoo Payment OS.**
