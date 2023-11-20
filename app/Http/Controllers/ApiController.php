<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Models\Booking;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiController extends Controller
{
    // findBookingSlots
    public function findBookingSlots(Request $request) 
    {
        // chek if mandatory fields are missing
        if (!$request->from && !$request->to || empty($request->from) && empty($request->to)) 
        {
            return response()->json(['message' =>  'Bad request. From and To Dates are required'], 403);
        }
        
        // if the date is past date then return as bad request
        $today = date('Y-m-d');
        if ($request->from < $today || $request->to < $today) 
        {
            return response()->json(['message' =>  'Bad request. You cannot choose date already past.'], 404);
        }

        // available bookings for the date range
        $availableBookingSlots = $this->getBookingSlotsByDateRange($request->from, $request->to);

        // if there are bookings already made then show remaining slots
        if ($availableBookingSlots !== null) 
        {
                return response()->json(['message' => $availableBookingSlots], 200);
        } 
        else 
        {
            return response()->json(['message' =>  'No Slots Available'], 204);
        }
    }
     
    // findBookingPrice
    public function findBookingPrice(Request $request) 
    {  
        // chek if mandatory fields are missing
         if (!$request->from && !$request->to) 
         {
            return response()->json(['message' =>  'Bad request. From and To Dates are required'], 403);
        }

        // if the date is past date then return as bad request   
        $today = date('Y-m-d');
        if ($request->from < $today || $request->to < $today) 
        {
            return response()->json(['message' =>  'Bad request. You cannot choose date already past.'], 404);
        }

        // booking price for the date range
        $bookingPrice = $this->checkBookingPrice($request->from, $request->to);

        // true then show the prices
        if ($bookingPrice) 
        {
            return response()->json($bookingPrice, 200);
        } 
        else 
        {
            return response()->json(['message' => 'Invalid date selection' ], 404);
        }
    }
 
    // createBooking
    public function createBooking(Request $request) 
    {    
        // chek if mandatory fields are missing
         if (!$request->from && !$request->to) 
         {
            return response()->json(['message' =>  'Bad request. From and To Dates are required'], 403);
        }

        // if the date is past date then return as bad request       
        $today = date('Y-m-d');
        if ($request->from < $today || $request->to < $today) 
        {
            return response()->json(['message' =>  'Bad request. You cannot choose date already past.'], 404);
        }
        
        // create booking slot
        $createBookingSlot = $this->createBookingSlot($request->from, $request->to);

        // if created then show the slot number with date range
        if ($createBookingSlot) 
        {
            $bookingSlots = $this->getLatestBookingSlot($request->from, $request->to);
            foreach ($bookingSlots as $index=>$slot) 
            {
                return response()->json(['message' => 'Booking slot: '.$slot->bookingSlot.' confirmed between '.date('d/m/Y',strtotime($slot->bookingFromDate)).' and '.date('d/m/Y',strtotime($slot->bookingEndDate)).'' ], 201);
            }
        } 
        else 
        {
            return response()->json(['message' => 'All Booking slots are booked for these dates between '.date('d/m/Y',strtotime($request->from)).' and '.date('d/m/Y',strtotime($request->to)).'' ], 202);
        }
    }
 
    // cancelBooking
    public function cancelBooking(Request $request) 
    {  
        // chek if mandatory fields are missing
         if (!$request->customerId) 
         {
            return response()->json(['message' =>  'Bad request. CustomerId is required'], 403);
        }

        // send the customer id and cancel the booking      
        $deleted = $this->deleteBooking($request->customerId);

        // if found delete else show no bookings found for the customer
        if ($deleted) 
        {
            return response()->json(['message' => 'Booking Cancelled Successfully!' ], 200);
        } 
        else 
        {
            return response()->json(['message' => 'No bookings found for this customer' ], 202);
        }
    }
 
    // updateBooking
    public function updateBooking(Request $request) 
    {   
        // chek if mandatory fields are missing
         if (!$request->customerId && !$request->from && !$request->to) 
         {
            return response()->json(['message' =>  'Bad request. From Date, To Date and CustomerId are required'], 403);
        }

        // if the date is past date then return as bad request            
        $today = date('Y-m-d');
        if ($request->from < $today || $request->to < $today) 
        {
            return response()->json(['message' =>  'Bad request. You cannot choose date already past.'], 404);
        }

        // input customer id and date range to update the booking
        $updateBookingSlot = $this->updateBookingSlot($request->customerId, $request->from, $request->to);
        if ($updateBookingSlot) 
        {
            $bookingSlots = $this->getLatestBookingSlot($request->from, $request->to);
            foreach ($bookingSlots as $index=>$slot){
                return response()->json(['message' => 'Booking Updated Successfully!' ], 201);
            }
        } 
        else 
        {
            return response()->json(['message' => 'No booking slots found for this customer' ], 202);
        }
    }

    // find booking slots
    public function getBookingSlotsByDateRange($from, $to) 
    {
        $slots = [];
        $slotsPerDay = 10;
        $newSlots = [];
        $start = new DateTime($from);
        $end   = new DateTime($to);
        for($i = $start; $i <= $end; $i->modify('+1 day')) 
        {
            $to = $i->format('Y-m-d');
            $slots = DB::table('bookings')
                    ->select(DB::raw('DATE(bookingEndDate) AS Date'),DB::raw('COUNT(bookingSlot) AS Slots_Available'))
                    ->where('bookingEndDate', '>=', Carbon::now()->subDays($this->findNumberOfDays($from,$to)))
                    ->groupBy(DB::raw('DATE(bookingEndDate)'))
                    ->orderBy(DB::raw('DATE(bookingEndDate)'))
                    ->get()->toArray();
            $days[] = $to;
            $newSlots = $slots;
        }
        $count = 0;
        $bookingSlots = [];
        foreach($days as $eachDay) 
        {
            foreach($newSlots as $index=>$slot) 
            {
                if($days[$count] == $slot->Date && $slot->Slots_Available != 0) 
                {
                    $bookingSlots[$count]['Date'] = $slot->Date;
                    $bookingSlots[$count]['Slots_Available'] = $slotsPerDay - $slot->Slots_Available;
                    break;
                } 
                else 
                {
                    $bookingSlots[$count]['Date'] = $eachDay;
                    $bookingSlots[$count]['Slots_Available'] = 10;
                }
            }
        $count++;
        }
        if ($bookingSlots == null) 
        {
            foreach($days as $eachDay) 
            {
                $bookingSlots[$count]['Date'] = $eachDay;
                $bookingSlots[$count]['Slots_Available'] = 10;
                $count++;
            }
        }
        return $bookingSlots;
    }

    // create booking function
    public function createBookingSlot($from, $to) 
    {
        $availableBookings = $this->getSingleBooking($from, $to);
        $bookingSlot = 1; 
        foreach($availableBookings as $index=>$availableBooking){
            if ($availableBooking === null) 
            {
                $bookingSlot = 1;
            } 
            else 
            {
                if (isset($availableBooking->bookingSlot) && $availableBooking->bookingSlot < 10){
                    if ($from == $to) 
                    {
                        $bookingSlot = $availableBooking->bookingSlot+1;
                    }
                    if ($from != $to) 
                    {
                        $updatedAvailableBookings = $this->getLatestBookingSlot($from, $to);
                        foreach($updatedAvailableBookings as $index=>$updatedAvailableBooking){
                        if ($updatedAvailableBooking === null){
                            $bookingSlot = 1;
                        } 
                        else 
                        {
                                if ($updatedAvailableBooking->BookingFromDate = $from && $updatedAvailableBooking->BookingEndDate = $to && $updatedAvailableBooking->bookingSlot < 10) 
                                {
                                    $bookingSlot = $updatedAvailableBooking->bookingSlot+1;
                                }
                            }
                        }
                    }
                } 
                else 
                {
                    return false;
                }
            }
        }
        $booking = [
            'bookingSlot'=>$bookingSlot,
            'customerId'=>Str::uuid()->toString(),
            'bookingFromDate'=>$from,
            'bookingEndDate'=>$to,
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ];
        $bookingId = DB::table('bookings')->insert($booking);
        if (!$bookingId) 
        {
            return false;
        }
        return true;   
    }

    // find booking price function
    public function checkBookingPrice($from, $to) 
    {
        $fromDateString = explode('-',$from);
        $toDateString = explode('-',$to);
        $winterWeekDayPrice = 24.99;
        $winterWeekEndPrice = 28.99;
        $summerWeekDayPrice = 34.99;
        $summerWeekEndPrice = 38.99;
        $numberOfDays = $this->findNumberOfDays($from, $to);
        if (($from >= $fromDateString[0].'-11-01' && $to >= $toDateString[0].'-11-01') || ($from <= $fromDateString[0].'-03-31' && $to <= $toDateString[0].'-03-31')) 
        {
            if ($this->checkWeekend($from, $to)) 
            {
                return $bookingPrice = ['price' =>'£'.round($winterWeekEndPrice+$numberOfDays*1.5, 2), 'weekType'=> 'Weekend', 'priceType'=>'Winter', 'bookingDays'=>$numberOfDays];
            } 
            else 
            {
                return $bookingPrice = ['price' =>'£'.round($winterWeekDayPrice+$numberOfDays*1.5, 2), 'weekType'=> 'Weekday', 'priceType'=>'Winter', 'bookingDays'=>$numberOfDays];
            }
        } 
        else 
        {
            if ($this->checkWeekend($from, $to)) 
            {
                return $bookingPrice = ['price' =>'£'.round($summerWeekEndPrice+$numberOfDays*2, 2), 'weekType'=> 'Weekend', 'priceType'=>'Summer', 'bookingDays'=>$numberOfDays];
            } 
            else 
            {
                return $bookingPrice = ['price' =>'£'.round($summerWeekEndPrice+$numberOfDays*2, 2), 'weekType'=> 'Weekday', 'priceType'=>'Summer', 'bookingDays'=>$numberOfDays];
            }
        }
    }

    // cancel booking function
    public function deleteBooking($customerId) 
    {
        $currentBookings = DB::table('bookings')
                        ->select('bookingId')
                        ->where('customerId', $customerId)
                        ->get()->toArray();
        foreach($currentBookings as $index=>$currentBooking){
            if ($currentBooking) 
            {
                DB::table('bookings')->where('bookingId',$currentBooking->bookingId)->delete();
                return true;
            } 
            else 
            {
                return false;
            }
        }
    }
 
    // update booking function
    public function updateBookingSlot($customerId, $from, $to) 
    {
        $currentBookings = $this->getBookingByCustomer($customerId);
        if (!$currentBookings) 
        {
            return false;
        }
        $bookingSlot = 1; 
        $bookingId = null;
        foreach($currentBookings as $index=>$currentBooking){
            $bookingId = $currentBooking->bookingId;
        if ($currentBooking === null) 
        {
                $bookingSlot = 1;
        } 
        else 
        {
                if (isset($currentBooking->bookingSlot) && $currentBooking->bookingSlot <= 10){
                    if ($from == $to){
                        $bookingSlot = $currentBooking->bookingSlot+1;
                    }
                    if ($from != $to) 
                    {
                        $updatedAvailableBookings = $this->getLatestBookingSlot($from, $to);
                        foreach($updatedAvailableBookings as $index=>$updatedAvailableBooking){
                        if ($updatedAvailableBooking === null) 
                        {
                            $bookingSlot = 1;
                        } 
                        else 
                        {
                                if ($updatedAvailableBooking->BookingFromDate = $from && $updatedAvailableBooking->BookingEndDate = $to && $updatedAvailableBooking->bookingSlot < 10) 
                                {
                                    $bookingSlot = $updatedAvailableBooking->bookingSlot+1;
                                }
                            }
                        }
                    }
                } 
                else 
                {
                    return false;
                }
            }
        }
        $booking = [
            'bookingSlot'=>$bookingSlot,
            'customerId'=>$customerId,
            'bookingFromDate'=>$from,
            'bookingEndDate'=>$to,
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ];
        $bookingId = DB::table('bookings')
                    ->where('bookingId', $bookingId)
                    ->update($booking);
        if (!$bookingId) 
        {
            return false;
        }
        return true;    
    }
 
    public function getNumberOfDays($from, $to) 
    {
        $date1 = strtotime($from);
        $date2 = strtotime($to);
        $diff = $date2 - $date1;
        $days = floor($diff / (60 * 60 * 24));
        return $days;
    }

    public function checkWeekend($from, $to) 
    {
        $is_weekend = 0;
        $fromDate = strtotime($from);
        $toDate = strtotime($to);
        while (date('Y-m-d', $fromDate) != date('Y-m-d', $toDate)) 
        {
            $day = date('w', $fromDate);
            if ($day == 0 || $day == 6) 
            {
                $is_weekend = 1;
            }
            $fromDate = strtotime(date('Y-m-d', $fromDate) . '+1 day');
        }
        return $is_weekend;
    }
 
    public function findNumberOfDays($from, $to) 
    {
        $date1 = new DateTime($from);
        $date2 = new DateTime($to);
        $interval = $date1->diff($date2);
        return $interval->days;
    }
 
    public function getLatestBookingSlot($from, $to) 
    {
        return DB::table('bookings')
            ->select('*')
            ->whereBetween('bookingFromDate', [$from, $to])
            ->whereBetween('bookingEndDate', [$from, $to])
            ->orderBy('created_at','desc')
            ->limit(1)
            ->get()->toArray();
    }

    public function getSingleBooking($from, $to) 
    {
        if ($from == $to) 
        {
            return DB::table('bookings')
                ->select('*')
                ->where('bookingFromDate', $from)
                ->where('bookingEndDate', $to)
                ->orderBy('bookingSlot','desc')
                ->limit(1)
                ->get()->toArray();
        } 
        else 
        {
            return DB::table('bookings')
                ->select('*')
                ->where('bookingFromDate', $from)
                ->where('bookingEndDate', $to)
                ->orderBy('bookingSlot','desc')
                ->limit(1)
                ->get()->toArray();
        }
    }

    public function getBookingByCustomer($customerId) 
    {
        return DB::table('bookings')
                ->select('*')
                ->where('customerId', $customerId)
                ->LIMIT(1)
                ->get()->toArray();
    }
}
