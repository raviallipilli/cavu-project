<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Support\Str;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateBooking()
    {
        // Test for missing fields
         $response = $this->call('POST','/api/create-booking', [
        ]);
        $response->assertJson([
            'message'=> 'Bad request. From and To Dates are required'], false);
        $this->assertEquals(403, $response->status());

        // Create a booking for a date range
        $response = $this->call('POST','/api/create-booking', [
            'from' => '2023-11-30',
            'to' => '2023-12-01'
        ]);
        $response->assertJson([
            'message'=> 'Booking slot: 1 confirmed between 30/11/2023 and 01/12/2023'], false);
        $this->assertEquals(201, $response->status());

        // Test pricing for a past date
        $response = $this->call('POST','/api/create-booking', [
            'from' => '2023-11-16',
            'to' => '2023-11-17'
        ]);
        $this->assertEquals(404, $response->status());
    }

    public function testFindBookingSlots()
    {
        // Test for missing fields
        $response = $this->call('GET','/api/find-booking-slots', [
        ]);
        $response->assertJson([
            'message'=> 'Bad request. From and To Dates are required'], false);
        $this->assertEquals(403, $response->status());

         // Test find available Booking slots for a date range
         $response = $this->call('GET', '/api/find-booking-slots', [
            'from' => '2023-11-29',
            'to' => '2023-12-04'
        ]);
        $response->assertJson([
                'message'=> [
                    [
                        'Date'=> '2023-11-29',
                        'Slots_Available'=> 10
                    ],
                    [
                        'Date'=> '2023-11-30',
                        'Slots_Available'=> 10
                    ],
                    [
                        'Date'=> '2023-12-01',
                        'Slots_Available'=> 9
                    ],
                    [
                        'Date'=> '2023-12-02',
                        'Slots_Available'=> 10
                    ],
                    [
                        'Date'=> '2023-12-03',
                        'Slots_Available'=> 10
                    ],
                    [
                        'Date'=> '2023-12-04',
                        'Slots_Available'=> 10
                    ]
                ]
            ], false);
        $this->assertEquals(200, $response->status());

        // Test for a different date range - find available Booking slots for a date range
        $response = $this->call('GET', '/api/find-booking-slots', [
            'from' => '2023-12-14',
            'to' => '2023-12-16'
        ]);
        $response->assertJson([
                'message'=> [
                [
                    'Date'=> '2023-12-14',
                    'Slots_Available'=> 10
                ],
                [
                    'Date'=> '2023-12-15',
                    'Slots_Available'=> 10
                ],
                [
                    'Date'=> '2023-12-16',
                    'Slots_Available'=> 10
                ]
                ]], false);
        $this->assertEquals(200, $response->status());
        
        // Create a booking for a date range and then check availability again
        $booking = Booking::create([
            'bookingSlot' => 1,
            'customerId'=>Str::uuid()->toString(),
            'bookingFromDate' => '2023-12-07',
            'bookingEndDate' => '2023-12-08',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);
        $response = $this->call('GET', '/api/find-booking-slots', [
            'from' => '2023-12-07',
            'to' => '2023-12-08'
        ]);
        $response->assertJson([
            'message'=> [
                [
                    'Date'=> '2023-12-07',
                    'Slots_Available'=> 10
                ],
                [
                    'Date'=> '2023-12-08',
                    'Slots_Available'=> 9
                ]
            ]], false);
        $this->assertEquals(200, $response->status());

        // Test for a date which is past
        $response = $this->call('GET', '/api/find-booking-slots', [
            'from' => '2023-11-16',
            'to' => '2023-11-17'
        ]);
        $this->assertEquals(404, $response->status());
    }

    public function testFindBookingPrice()
    {
        // Test for missing fields
         $response = $this->call('GET','/api/find-booking-price', [
        ]);
        $response->assertJson([
            'message'=> 'Bad request. From and To Dates are required'], false);
        $this->assertEquals(403, $response->status());

        // Test pricing for a date range
        $response = $this->call('GET', '/api/find-booking-price', [
            'from' => '2023-11-30',
            'to' => '2023-12-01'
        ]);
        $response->assertJson([
            'price'=> '£26.49',
            'weekType'=> 'Weekday',
            'priceType'=> 'Winter',
            'bookingDays'=> 1], false);
        $this->assertEquals(200, $response->status());

        // Test for another date range 
        $response = $this->call('GET', '/api/find-booking-price', [
            'from' => '2024-05-19',
            'to' => '2024-05-23'
        ]);
        $response->assertJson([
            'price'=> '£46.99',
            'weekType'=> 'Weekend',
            'priceType'=> 'Summer',
            'bookingDays'=> 4], false);
        $this->assertEquals(200, $response->status());

        // Test pricing for a past date
        $response = $this->call('GET', '/api/find-booking-price', [
            'from' => '2023-11-16',
            'to' => '2023-11-17'
        ]);
        $response->assertJson([
            'message' => 'Bad request. You cannot choose date already past.'], false);
        $this->assertEquals(404, $response->status());

    }

    public function testCancelBooking()
    {
        // Test for missing fields
         $response = $this->call('POST','/api/cancel-booking', [
        ]);
        $response->assertJson([
            'message'=> 'Bad request. CustomerId is required'], false);
        $this->assertEquals(403, $response->status());

        // Test first create a booking witn a date range
        $booking = Booking::create([
            'bookingSlot' => 1,
            'customerId'=>Str::uuid()->toString(),
            'bookingFromDate' => '2023-12-09',
            'bookingEndDate' => '2023-12-10',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        // Test to cancel the booking for the customer which is created above
        $response = $this->call('POST','/api/cancel-booking', [
            'customerId' => $booking->customerId
        ]);
        $response->assertJson([
            'message'=> 'Booking Cancelled Successfully!'], false);
        $this->assertEquals(200, $response->status());

        // Test - try to delete the same customer with the above dates
        $response = $this->call('POST','/api/cancel-booking', [
            'customerId' => $booking->customerId
        ]);
        $response->assertJson([
            'message'=> 'No bookings found for this customer'], false);
        $this->assertEquals(202, $response->status());
    }

    public function testUpdateBooking()
    {
        // Test for missing fields
         $response = $this->call('POST','/api/update-booking', [
        ]);
        $response->assertJson([
            'message'=> 'Bad request. From Date, To Date and CustomerId are required'], false);
        $this->assertEquals(403, $response->status());

        // Test first create a booking with a date range
        $booking = Booking::create([
            'bookingSlot' => 1,
            'customerId'=>Str::uuid()->toString(),
            'bookingFromDate' => '2023-11-29',
            'bookingEndDate' => '2023-11-30',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        // Update a booking for a date range
        $response = $this->call('POST','/api/update-booking', [
            'customerId' => $booking->customerId,
            'from' => '2023-12-02',
            'to' => '2023-12-03'
        ]);
        $response->assertJson([
            'message'=> 'Booking Updated Successfully!'], false);
        $this->assertEquals(201, $response->status());

        // Test to update for a past date
        $response = $this->call('POST','/api/update-booking', [
            'from' => '2023-11-16',
            'to' => '2023-11-17'
        ]);
        $this->assertEquals(404, $response->status());
    }
}
