# Horrible Booking System
This is a hotel room management system made by a young receptionist with no programming experience *in 2016*.
It's definitely not made to be used in production environments.
It has tons of (sometimes very basic) security issues and must not be used as it is (also due to very poor code design).

![The site in action](risorse/example.gif)

## A bit of context
In 2016, I was working for a hotel with almost 100 rooms and they asked me to be in charge for the next summer season.
At that time all the bookings and rooms assignations were done manually on a big paper calendar.

I decided then that I would have tried to do a booking manager and room assignation system in PHP (which was the only thing I knew a bit about).
Just few days before the 2016 summer season started my program was ready(ish).
Through the season I had freed so much time to focus on improving hotel performance and got better statistics on occupancy that the hotel revenues had a growth of 25% YoY for two consecutive summer seasons.

Also, this project meant a lot to me in terms of programming. Having spent a lot of hours fixing and refactoring it and with a total of more than 10.000 lines of (very bad quality) code, I still remember with joy the struggle and the challenge it meant to me and can't help but feel a bit proud of this (once again, messy and hugly) piece of code.

## Features I'm proud of
- Autoassign rooms to bookings for big groups (beta)
- Notes and errors are grouped in the calendar to rapidly find and fix them
- Can have bookings not assigned to any room
- Overbookings are handled and marked in red
- A booking can have additional rooms associated with it
- Paxes can change throughout the stay and room shares are properly handled
- Possibility to print specific mouvements lists for housekeeping, restaurant and reception
- Analytics for meals, stays and occupancy rates with possibility to filter by agency and/or groups
- Smart dates range to have a view on specific weeks/months/seasons

## How to use it (if you really want to)
If you want to test it you would need to:
- Create a new `MySql` database
- Import the basic tables structure in `risorse/structure.sql`
- Modify database credentials in `funzioni_admin.php`
- The default user for the website is `admin` and the password is `password`
- To change the user you can edit `$adminName` and `$adminPass` in `index.php`
- Create some rooms in the menu `Gestione camere`
- You are ready to enter some bookings :)

## Requirements
Somehow the site still works on:
- PHP 7.4.3
- Mysql 8.0.28
- Apache 2.4.41