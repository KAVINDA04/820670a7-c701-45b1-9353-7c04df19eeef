\# Laravel Student Report CLI Application



This is a command-line tool to generate \*\*Diagnostic\*\*, \*\*Progress\*\*, and \*\*Feedback\*\* reports for students. It includes automated tests.



The project also includes \*\*Docker Compose\*\* support to simplify running the application and tests without installing PHP, Composer, or dependencies locally.



\# Requirements



\- Docker Desktop (Windows, macOS, Linux)

\- Docker Compose (included in recent Docker Desktop versions)



\# Setup



1\. Clone the repository:



git clone https://github.com/KAVINDA04/820670a7-c701-45b1-9353-7c04df19eeef.git

cd assessment-reporter



2\. Running the Application (Manual Testing)



docker compose build

docker compose run --rm app



3\. Running Automated Tests



docker compose run --rm test

