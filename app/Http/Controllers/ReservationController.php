<?php

namespace App\Http\Controllers;

use Log;
use DateTime;
use DateInterval;
use App\Reservation;
use App\Table;
use App\Guest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReservationController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $reservations = Reservation::orderBy('start_time', 'ASC')->get();
    //$reservations = Reservation::all()->orderBy('start_time', 'DESC')->get();

    foreach ($reservations as $reservation) {
      $table = Table::find($reservation->table_id);
      $guest = Guest::find($reservation->guest_id);
      if(isset($table) && isset($guest)){
        $reservation->table = $table;
        $reservation->$guest = $guest;
      }
    }

  //  print('<pre>');
  //  var_dump($reservations);
    return view('admin.reservations_show', ['reservations' => $reservations]);
  }

  public function selectDate(Request $request)
  {
    if (!isset($request->date)){
      return view('admin.reservation_add_selectDate');
    }

    $today = new DateTime();
    $start = new DateTime($request->date);
    $end = new DateTime($request->date);
    $end->add(new DateInterval('P1D'));

    $reservations = Reservation::whereRaw('start_time >= ? AND end_time < ? ORDER BY start_time ASC', [
      $start->format('Y-m-d'), $end->format('Y-m-d')
    ])->get();
    return view('admin.reservation_add_selectDate', [
      'tables'=>Table::all(),
      'reservations'=>$reservations,
      'date'=>$start,
      'party'=>isset($request->party)? $request->party: 0
    ]);
  }
  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create($date, $party, $id)
  {

    $temp = explode('-', $id);
    $hour = floor($temp[0]/100);
    $minutes = ($temp[0]%100/100*60);
    $start_time = new DateTime($date);
    $start_time->add(new DateInterval('PT' . $hour . 'H'));
    $start_time->add(new DateInterval('PT' . $minutes . 'M'));
    $temp_time = clone $start_time;
    $end_time = $temp_time->add(new DateInterval('PT90M'));
    return view('admin.reservation_add',[
      'start_time' => $start_time,
      'end_time' => $end_time,
      'party' => $party,
      'tableId' => $temp[1]
    ]);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    if(!isset($request->table_id, $request->party,
      $request->start_time, $request->end_time,
      $request->customer_name, $request->customer_phone) ){
      return view('layouts.results', [
        'redirect' => '/reservations',
        'msg'=>'Missing information',
        'status'=>'error'
      ]);
    }
    $end_time = new DateTime($request->date . " " . $request->end_time);
    $start_time = new DateTime($request->start_time);
    if($start_time > $end_time){
      return view('layouts.results', [
        'redirect' => '/reservations',
        'msg'=>'End time is before start time',
        'status'=>'error'
      ]);
    }
    $guest = new Guest;
    $guest->name = $request->customer_name;
    $guest->phone = $request->customer_phone;
    $guest->email = (isset($request->customer_email)) ? $request->customer_email : 'N/A';
    $guest->save();

    $reservation = new Reservation;
    $reservation->start_time = $start_time;
    $reservation->end_time = $end_time;
    $reservation->party_size = $request->party;
    $reservation->guest_id = $guest->id;
    $reservation->table_id = $request->table_id;
    $reservation->save();

    return view('layouts.results', [
      'redirect' => '/reservations',
      'msg'=>'New Guest and Reservation Added',
      'status'=>'success'
    ]);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    $reservation = Reservation::find($id);
    $guest = Guest::find($reservation->guest_id);
    $reservation->guest_name = $guest->name;
    $reservation->guest_phone = $guest->phone;
    $reservation->guest_email = $guest->email;

      return view('layouts.show', [
        'title'=>'Reservation',
        'model' => $reservation
      ]);
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
      //return view('admin.table_edit', ['table' => Table::find($id)]);
      return view('layouts.results', [
        'redirect' => '/reservations',
        'msg'=>'Feature not available. Please delete reservation and create a new one.',
        'status'=>'warning'
      ]);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
      // if(!isset($request->name) || !isset($request->seats) ){
      //   return view('layouts.results', [
      //     'redirect' => '/tables',
      //     'msg'=>'Table information missing',
      //     'status'=>'error'
      //   ]);
      // }
      // $table = Table::find($id);
      // $table->name =  $request->name;
      // $table->seats = $request->seats;
      // $result = $table->save();
      //
      // if($result){
      //   return view('layouts.results', [
      //     'redirect' => '/tables',
      //     'msg'=>'Table Updated: ' . $table->name,
      //     'status'=>'success'
      //   ]);
      // }else{
      //   return view('layouts.results', [
      //     'redirect' => '/tables',
      //     'msg'=>'Update Failed',
      //     'status'=>'error'
      //   ]);
      // }
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $reservation = Reservation::find($id);
    $result = Reservation::destroy($id);
    if($result){
      return view('layouts.results', [
        'redirect' => '/reservations',
        'msg'=>'Reservation Deleted: ' . $reservation->name,
        'status'=>'success'
      ]);
    }else{
      return view('layouts.results', [
        'redirect' => '/reservations',
        'msg'=>'Deletion failed',
        'status'=>'error'
      ]);
    }

  }
  public function calendar(){
    return view('admin.calendar');
  }
  public function reservationByDate($date){
    //list reservations for single date

    $start = new DateTime($date);
    $end = new DateTime($date);
    $end->add(new DateInterval('P1D'));

    $reservations = Reservation::whereRaw('start_time >= ? AND end_time < ? ORDER BY start_time ASC', [
      $start->format('Y-m-d'), $end->format('Y-m-d')
    ])->get();

    return view('admin.reservation_by_date', [
      'reservations' => $reservations,
      'tables' => Table::all(),
      'date' => $start->format('M d, Y')
    ]);
  }

}
