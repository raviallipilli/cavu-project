# Author - Ravi Allipilli
# Project - CAVU - CRUD api design for parking spaces 
# php version - 8.1
# laravel version - 10.10

# About the project:
# Requirements:
There is a car park at the Manchester Airport.
The car park has 10 spaces available so only 10 cars can be parked at the same time. Customers 
should be able to park a car for a given period (i.e., 10 days).

# Project covers:
• Customers should be able to check if there’s an available car parking space for a given 
date range.
• Customer should be able to check parking price for the given dates (i.e., 
Weekday/Weekend Pricing, Summer/Winter Pricing)
• Customers should be able to create a booking for given dates (from - to)
• Customers should be able to cancel their booking.
• Customers should be able to amend their booking.

Note: what it doesn't cover is the time on a given day, it will only work for given dates

# It also covers:
• Number of available spaces
• API should show how many spaces for given date is available (per day)
• Parking date From - date when car is being dropped off at the car park.
• Parking date To - date time when car will be picked from the car park.

# Unit Testing is also covered

# Steps to run the project:
1. clone the project
2. run migrate command
3. start your xamppp/wampp server
4. test the api's using:

    • http://127.0.0.1:8000/api/find-booking-slots - GET
    • http://127.0.0.1:8000/api/find-booking-price - GET
    • http://127.0.0.1:8000/api/create-booking - POST
    • http://127.0.0.1:8000/api/cancel-booking - POST
    • http://127.0.0.1:8000/api/update-booking - POST
5. run laravel test command to cover all the unit tests

