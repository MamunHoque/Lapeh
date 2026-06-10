class StatusMeta {
  final String label;
  final String tone; // green, blue, indigo, pink, amber, grey, red
  const StatusMeta(this.label, this.tone);
}

StatusMeta statusMeta(String status) {
  switch (status) {
    case 'on_the_way': return const StatusMeta('On the way', 'blue');
    case 'picked_up': return const StatusMeta('Picked up', 'indigo');
    case 'searching_driver': return const StatusMeta('Searching', 'pink');
    case 'searching': return const StatusMeta('Searching', 'pink');
    case 'assigned': return const StatusMeta('Assigned', 'amber');
    case 'waiting_payment': return const StatusMeta('Pending pay', 'amber');
    case 'waiting_for_location': return const StatusMeta('Awaiting location', 'amber');
    case 'created': return const StatusMeta('Created', 'grey');
    case 'delivered': return const StatusMeta('Delivered', 'green');
    case 'cancelled': return const StatusMeta('Cancelled', 'grey');
    default: return StatusMeta(status.replaceAll('_', ' '), 'grey');
  }
}
