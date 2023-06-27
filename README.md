
# Veloz Framework

Veloz Framework is a PHP framework that provides a solid foundation for web application development, similar to Laravel. It consists of a skeleton repository, zaroxjh/veloz, and a framework repository, zaroxjh/veloz-framework. This README will guide you through the installation and configuration process.


## Installation

To install the Veloz Framework, you will need to clone the skeleton repository and perform a few configuration steps. Follow the instructions below:


1. Clone the skeleton repository:

```
git clone https://github.com/zaroxjh/veloz.git
```

2. Remove the Git version control from the cloned repository:

```
cd veloz
rm -rf .git
```

3. Install PHP dependencies using Composer:

```
composer install
```

4. Install JavaScript dependencies using npm:

```
npm install
```

## Configuration

After the installation, you need to configure the environment variables. Copy the provided .env-example file to create a new .env file:

1. Create the .env file

```
cp .env-example .env
```

2. Open the .env file and modify the variables according to your needs.

**Note:** Ensure that you use http or https correctly based on your situation. If running locally, use http://localhost:8888/example as the **APP_URL**. On a server, adjust it accordingly.

Additionally, make sure your server configuration points all requests to the public folder. the skeleton provides an example of an .htaccess file for Apache servers. Note that for Nginx, the configuration will be different.

## Usage

Once you have completed the installation and configuration steps, you can access your Veloz Framework application using the URL you specified in the APP_URL variable.

## Updating

To update the Veloz Framework, you can simply run the following command to update the dependencies:

```
composer update
```

**Note:** The installation and update process may change in the future to incorporate the use of composer create-project, which will automatically fetch the skeleton and framework repositories. However, as of now, the aforementioned installation steps should be followed.
