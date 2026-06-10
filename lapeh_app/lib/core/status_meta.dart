import 'i18n.dart';

class StatusMeta {
  final String label;
  final String tone; // green, blue, indigo, pink, amber, grey, red
  const StatusMeta(this.label, this.tone);
}

/// Tones for every backend order status (orders.status enum).
const Map<String, String> _statusTones = {
  'created': 'grey',
  'waiting_for_location': 'amber',
  'location_confirmed': 'blue',
  'waiting_for_payment': 'amber',
  'paid': 'green',
  'searching_driver': 'pink',
  'driver_assigned': 'blue',
  'arrived_at_restaurant': 'indigo',
  'picked_up': 'indigo',
  'on_the_way': 'blue',
  'delivered': 'green',
  'cancelled': 'grey',
};

StatusMeta statusMeta(String status) {
  return StatusMeta(
    trOr('st_$status', status.replaceAll('_', ' ')),
    _statusTones[status] ?? 'grey',
  );
}

/// Statuses where a driver is actively working the order.
const driverActiveStatuses = ['driver_assigned', 'arrived_at_restaurant', 'picked_up', 'on_the_way'];

/// Statuses before the customer has paid (order still pending customer action).
const customerPendingStatuses = ['created', 'waiting_for_location', 'location_confirmed', 'waiting_for_payment'];
