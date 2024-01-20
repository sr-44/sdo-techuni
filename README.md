# Telegram Bot for Tajik Technical University students

This README provides step-by-step instructions to set up and run the Telegram bot, written in PHP. Please follow these guidelines carefully for a successful configuration.
## Prerequisites
1. **PHP Environment:** Ensure that you have PHP installed on your system.
2. **Composer Packages:** Download the required packages using Composer. Run the following command in your terminal:

```bash
composer install
```

3. **Configure .env File:** Manually configure the .env file with the following information:
        ENCRYPTION_KEY: Base64-encoded encryption key
        DB_USER: Database login
        DB_PASS: Database password
        API_KEY: Telegram API token
        Other environment configurations

4. **Database Setup:** Execute the following command to create necessary tables:

```bash
php app/helpers/create_table.php
```
## How to Run

After completing the setup, you can run the Telegram bot. Use the following command:

```bash
php bot.php
```
## Contributing

Feel free to contribute to the development of this Telegram bot by submitting pull requests.
Issues
## Issues
If you encounter any issues or have questions, please open an issue on the GitHub repository.