@php
$tones = [
    'created' => 'grey',
    'waiting_for_location' => 'amber',
    'location_confirmed' => 'blue',
    'waiting_for_payment' => 'amber',
    'paid' => 'green',
    'searching_driver' => 'pink',
    'driver_assigned' => 'blue',
    'arrived_at_restaurant' => 'indigo',
    'picked_up' => 'indigo',
    'on_the_way' => 'blue',
    'delivered' => 'green',
    'cancelled' => 'grey',
];
$tone = $tones[$status] ?? 'grey';
$label = \Illuminate\Support\Facades\Lang::has('admin.st_'.$status) ? __('admin.st_'.$status) : ucfirst(str_replace('_', ' ', $status));
@endphp
<span class="badge badge-{{ $tone }}">{{ $label }}</span>
