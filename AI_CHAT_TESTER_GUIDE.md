# 🤖 AI Chat Tester - User Guide

## Overview

The AI Chat Tester is a powerful real-time testing tool that allows you to interact with your e-commerce AI bot and see exactly how it will respond to customer messages before going live.

## Features

### ✅ Real-Time Testing
- Send messages and receive instant AI responses
- See exactly what customers will experience
- Test different conversation scenarios

### ✅ Debug Mode
- View detailed response information
- See AI actions and metadata
- Understand bot decision-making process

### ✅ Quick Test Scenarios
Pre-built test cases for common interactions:
- **Browse Catalog** - "Show me products"
- **Purchase Intent** - "I want to buy iPhone"
- **Price Inquiry** - "What's on sale?"
- **Order Tracking** - "Track my order"
- **Support Request** - "I have a problem"
- **Product Comparison** - "Compare products"
- **Greeting** - "Hello"

### ✅ Configuration Status
Real-time display of:
- E-commerce setup status
- AI mode enabled/disabled
- OpenAI API key status
- Current AI model being used

## How to Access

### Method 1: From Dashboard
1. Navigate to your E-commerce Dashboard
2. Look for the **Quick Actions** section
3. Click on **"🤖 Test AI Bot"**

### Method 2: Direct URL
Navigate to:
```
https://yourdomain.com/subdomain/{your-subdomain}/abc/ecommerce/ai-chat-tester
```

## How to Use

### Basic Testing

1. **Type a Message**
   - Enter your test message in the input field at the bottom
   - Click "Send 📤" or press Enter

2. **View Bot Response**
   - The AI bot will process your message
   - You'll see the response appear in the chat window
   - Interactive buttons (if any) will be displayed

3. **Continue the Conversation**
   - Keep testing with follow-up messages
   - The bot maintains conversation context
   - Test complete customer journeys

### Quick Test Scenarios

1. **Click any Quick Test button** in the right sidebar
2. The system automatically sends that message
3. View the bot's response instantly
4. Perfect for rapid testing of common scenarios

### Debug Mode

1. **Enable Debug Mode**
   - Click the "🔍 Show Debug" button at the top
   
2. **View Debug Information**
   - Click the "🔍 Debug" link under any bot message
   - See detailed response data including:
     - Whether the message was handled
     - Response type
     - Number of buttons/actions
     - Full action details
     - AI metadata

3. **Analyze Bot Behavior**
   - Understand what actions the AI is taking
   - See recommendation logic
   - Debug any issues

### Reset Chat

- Click **"🔄 Reset Chat"** to start a fresh conversation
- Useful when testing different customer scenarios
- Clears all conversation history

## Configuration Requirements

### ⚠️ Before Testing

Ensure the following are configured:

1. **E-commerce Setup Complete**
   - Run the e-commerce setup wizard
   - Configure Google Sheets (if using)
   - Set up payment methods

2. **AI Mode Enabled**
   - Go to E-commerce Settings
   - Enable "AI Powered Mode"
   - Configure AI settings

3. **OpenAI API Key Set**
   - Add your OpenAI API key in settings
   - Select the AI model (GPT-3.5 Turbo, GPT-4, etc.)
   - Set temperature and max tokens

4. **Products Available**
   - Sync products from Google Sheets, OR
   - Add products manually
   - Ensure products are active and in stock

## Test Scenarios to Try

### 🛍️ Product Browsing
```
"Show me all products"
"What do you have in stock?"
"Browse catalog"
```

### 🔍 Product Search
```
"Find iPhone cases"
"Do you have headphones?"
"Show me electronics"
```

### 💰 Price Inquiries
```
"How much is the iPhone 13?"
"What's on sale?"
"Do you have any discounts?"
```

### 🛒 Ordering
```
"I want to buy [product name]"
"Add to cart"
"Checkout"
```

### 📦 Order Tracking
```
"Where is my order?"
"Track order #12345"
"When will my package arrive?"
```

### ❓ Support Requests
```
"I have a problem with my order"
"Need help"
"Can I return this?"
```

### 🆚 Comparisons
```
"Compare iPhone 13 vs iPhone 14"
"What's the difference between..."
"Which is better?"
```

## Understanding Bot Responses

### Message Types

1. **Text Response**
   - Plain text reply from the bot
   - Natural language answers

2. **Interactive Buttons**
   - Clickable action buttons
   - Quick reply options
   - Up to 3 buttons per message

3. **Product Listings**
   - Formatted product information
   - Prices, descriptions, availability
   - Product images (in actual WhatsApp)

### Response Indicators

- **✅ Green Check** - Successful AI processing
- **⚠️ Yellow Warning** - Configuration issues
- **❌ Red X** - Error in processing

## Tips for Effective Testing

### 1. Test Different Customer Types
- New customers (first-time inquiries)
- Returning customers (order tracking)
- Confused customers (unclear requests)
- Urgent requests (problems, complaints)

### 2. Test Edge Cases
- Misspelled product names
- Out-of-stock inquiries
- Invalid requests
- Multiple requests in one message

### 3. Test Full Customer Journeys
- Browse → Select → Add to Cart → Checkout
- Inquiry → Support → Resolution
- Compare → Decide → Purchase

### 4. Test AI Understanding
- Use different phrasings for the same request
- Try formal vs. casual language
- Test with emojis and slang
- Try different languages (if supported)

### 5. Monitor Response Quality
- Check for appropriate tone
- Verify product information accuracy
- Ensure recommendations are relevant
- Test personalization features

## Troubleshooting

### "⚠️ E-commerce is not fully configured"
**Solution:** Complete the e-commerce setup wizard first
- Go to E-commerce Dashboard
- Click "🚀 Start E-commerce Setup"
- Complete all 4 steps

### "❌ AI service unavailable"
**Possible causes:**
1. OpenAI API key not set
2. Invalid API key
3. API rate limit reached
4. Network connection issues

**Solution:**
- Verify API key in E-commerce Settings
- Check API key balance/limits
- Try again after a few moments

### No Products Showing in Responses
**Solution:**
- Sync products from Google Sheets
- Verify products are marked as "active"
- Check stock quantities are greater than 0

### Bot Not Understanding Messages
**Solution:**
- Enable debug mode to see what AI is detecting
- Adjust AI settings (temperature, max tokens)
- Try more specific product names
- Rephrase your request

## Advanced Features

### Conversation Context
The tester maintains conversation context just like real customer chats:
- Remembers previous messages
- Maintains shopping cart state
- Tracks customer preferences
- Session expires after 30 minutes of inactivity

### Session Management
Each test session:
- Creates a unique test contact
- Tracks all interactions
- Logs to ecommerce logs for debugging
- Isolates test data from real customers

## Best Practices

1. **Test Before Launching**
   - Test all major scenarios before going live
   - Train your team using the tester
   - Document common issues and solutions

2. **Regular Testing**
   - Test after configuration changes
   - Test new products
   - Test seasonal promotions
   - Test updated AI settings

3. **Share with Team**
   - Let support agents practice
   - Train new team members
   - Create response guidelines

4. **Monitor Logs**
   - Review test logs in ecommerce logs
   - Identify patterns in AI responses
   - Optimize based on insights

## Security & Privacy

- Test sessions use dummy contact data
- No real customer information is used
- Test interactions are clearly marked in logs
- Test data doesn't affect analytics

## Additional Resources

- [E-commerce System Overview](./ECOMMERCE_SYSTEM_OVERVIEW.md)
- [E-commerce Bot Setup Guide](./ECOMMERCE_BOT_SETUP.md)
- [Google Sheets Integration Guide](./GOOGLE_SHEETS_INTEGRATION_GUIDE.md)

## Support

If you encounter issues:
1. Check configuration status in the tester
2. Review logs: `storage/logs/laravel.log`
3. Enable debug mode for detailed information
4. Check ecommerce-specific logs

---

**🎉 Happy Testing!**

The AI Chat Tester helps ensure your e-commerce bot delivers perfect customer experiences every time.
