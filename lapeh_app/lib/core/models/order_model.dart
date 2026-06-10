/// Laravel serializes MySQL decimal columns as strings ("85.00"),
/// so numeric fields must accept both num and String.
double? asDouble(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

/// SQL aggregates (COUNT/SUM) also arrive as strings ("0").
int? asInt(dynamic v) {
  if (v == null) return null;
  if (v is int) return v;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString());
}

class OrderModel {
  final int id;
  final String orderNo;
  final String customerName;
  final String customerPhone;
  final String status;
  final String paymentStatus;
  final double orderValue;
  final double? deliveryFee;
  final double? totalAmount;
  final double? distanceKm;
  final String? customerAddress;
  final double? customerLat;
  final double? customerLng;
  final String? otp;
  final String? locationToken;
  final String? customerLink;
  final String? cancelledReason;
  final DateTime? assignedAt;
  final DateTime? pickedUpAt;
  final DateTime? deliveredAt;
  final DateTime createdAt;
  final OrderDriver? driver;
  final List<StatusLogEntry> timeline;
  final List<OrderItem> items;

  // Pickup info (from the sender; populated for driver order payloads)
  final String? pickupName;
  final double? pickupLat;
  final double? pickupLng;
  final String? pickupAddress;
  final String? pickupPhone;

  const OrderModel({
    required this.id,
    required this.orderNo,
    required this.customerName,
    required this.customerPhone,
    required this.status,
    required this.paymentStatus,
    required this.orderValue,
    this.deliveryFee,
    this.totalAmount,
    this.distanceKm,
    this.customerAddress,
    this.customerLat,
    this.customerLng,
    this.otp,
    this.locationToken,
    this.customerLink,
    this.cancelledReason,
    this.assignedAt,
    this.pickedUpAt,
    this.deliveredAt,
    required this.createdAt,
    this.driver,
    this.timeline = const [],
    this.items = const [],
    this.pickupName,
    this.pickupLat,
    this.pickupLng,
    this.pickupAddress,
    this.pickupPhone,
  });

  factory OrderModel.fromJson(Map<String, dynamic> j) {
    return OrderModel(
      id: j['id'],
      orderNo: j['order_no'],
      customerName: j['customer_name'] ?? '',
      customerPhone: j['customer_phone'] ?? '',
      status: j['status'],
      paymentStatus: j['payment_status'] ?? 'pending',
      orderValue: asDouble(j['order_value']) ?? 0,
      deliveryFee: asDouble(j['delivery_fee']),
      totalAmount: asDouble(j['total_amount']),
      distanceKm: asDouble(j['distance_km']),
      customerAddress: j['customer_address'],
      customerLat: asDouble(j['customer_lat']),
      customerLng: asDouble(j['customer_lng']),
      otp: j['otp_code'],
      locationToken: j['location_token'],
      customerLink: j['customer_link'],
      cancelledReason: j['cancelled_reason'],
      assignedAt: j['assigned_at'] != null ? DateTime.parse(j['assigned_at']) : null,
      pickedUpAt: j['picked_up_at'] != null ? DateTime.parse(j['picked_up_at']) : null,
      deliveredAt: j['delivered_at'] != null ? DateTime.parse(j['delivered_at']) : null,
      createdAt: j['created_at'] != null ? DateTime.parse(j['created_at']) : DateTime.now(),
      driver: j['driver'] != null ? OrderDriver.fromJson(j['driver']) : null,
      timeline: (j['status_timeline'] as List? ?? [])
          .map((e) => StatusLogEntry.fromJson(e))
          .toList(),
      items: (j['items'] as List? ?? [])
          .map((e) => OrderItem.fromJson(e))
          .toList(),
      pickupName: j['pickup_name'],
      pickupLat: asDouble(j['pickup_lat']),
      pickupLng: asDouble(j['pickup_lng']),
      pickupAddress: j['pickup_address'],
      pickupPhone: j['pickup_phone'],
    );
  }

  bool get isTerminal => status == 'delivered' || status == 'cancelled';
  bool get hasDriver => driver != null;
  bool get canRate => status == 'delivered' && hasDriver;
  bool get hasPickupCoords => pickupLat != null && pickupLng != null;
  bool get hasCustomerCoords => customerLat != null && customerLng != null;
}

class OrderItem {
  final String name;
  final int quantity;
  final double unitPrice;
  final double totalPrice;
  final String? description;

  const OrderItem({
    required this.name,
    required this.quantity,
    required this.unitPrice,
    required this.totalPrice,
    this.description,
  });

  factory OrderItem.fromJson(Map<String, dynamic> j) => OrderItem(
        name: j['name'] ?? '',
        quantity: asInt(j['quantity']) ?? 1,
        unitPrice: asDouble(j['unit_price']) ?? 0,
        totalPrice: asDouble(j['total_price']) ?? 0,
        description: j['description'],
      );

  Map<String, dynamic> toJson() => {
        'name': name,
        'quantity': quantity,
        'unit_price': unitPrice,
        if (description != null && description!.isNotEmpty) 'description': description,
      };
}

class OrderDriver {
  final int id;
  final String name;
  final String phone;
  final double? lat;
  final double? lng;
  final String vehicleType;

  const OrderDriver({
    required this.id,
    required this.name,
    required this.phone,
    this.lat,
    this.lng,
    required this.vehicleType,
  });

  factory OrderDriver.fromJson(Map<String, dynamic> j) => OrderDriver(
        id: j['id'],
        name: j['name'],
        phone: j['phone'],
        lat: asDouble(j['lat']),
        lng: asDouble(j['lng']),
        vehicleType: j['vehicle_type'],
      );
}

class StatusLogEntry {
  final String status;
  final String? note;
  final DateTime? at;

  const StatusLogEntry({required this.status, this.note, this.at});

  factory StatusLogEntry.fromJson(Map<String, dynamic> j) => StatusLogEntry(
        status: j['status'],
        note: j['note'],
        at: j['at'] != null ? DateTime.parse(j['at']) : null,
      );
}

class DeliveryOffer {
  final int id;
  final String orderNo;
  final String pickupName;
  final double pickupLat;
  final double pickupLng;
  final String pickupAddress;
  final double? deliveryFee;
  final double? distanceKm;
  final int timeoutSec;

  const DeliveryOffer({
    required this.id,
    required this.orderNo,
    required this.pickupName,
    required this.pickupLat,
    required this.pickupLng,
    required this.pickupAddress,
    this.deliveryFee,
    this.distanceKm,
    required this.timeoutSec,
  });

  factory DeliveryOffer.fromJson(Map<String, dynamic> j) => DeliveryOffer(
        id: j['id'],
        orderNo: j['order_no'],
        pickupName: j['pickup_name'] ?? '',
        pickupLat: asDouble(j['pickup_lat']) ?? 0,
        pickupLng: asDouble(j['pickup_lng']) ?? 0,
        pickupAddress: j['pickup_address'] ?? '',
        deliveryFee: asDouble(j['delivery_fee']),
        distanceKm: asDouble(j['distance_km']),
        timeoutSec: asInt(j['timeout_sec']) ?? 30,
      );
}

class DashboardStats {
  final int total;
  final int delivered;
  final int cancelled;
  final double revenue;

  const DashboardStats({
    required this.total,
    required this.delivered,
    required this.cancelled,
    required this.revenue,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> j) => DashboardStats(
        total: asInt(j['total']) ?? 0,
        delivered: asInt(j['delivered']) ?? 0,
        cancelled: asInt(j['cancelled']) ?? 0,
        revenue: asDouble(j['revenue']) ?? 0,
      );
}

class ReportData {
  final int orders, delivered, cancelled;
  final double revenue, avgFee, yesterdayRevenue;
  final List<ReportRow> recent;

  const ReportData({
    required this.orders,
    required this.delivered,
    required this.cancelled,
    required this.revenue,
    required this.avgFee,
    required this.yesterdayRevenue,
    required this.recent,
  });

  /// Percent change in today's revenue vs yesterday; null when no baseline.
  double? get revenueDeltaPct {
    if (yesterdayRevenue <= 0) return null;
    return (revenue - yesterdayRevenue) / yesterdayRevenue * 100;
  }

  factory ReportData.fromJson(Map<String, dynamic> j) {
    final today = (j['today'] as Map?) ?? const {};
    return ReportData(
      orders: asInt(today['orders']) ?? 0,
      delivered: asInt(today['delivered']) ?? 0,
      cancelled: asInt(today['cancelled']) ?? 0,
      revenue: asDouble(today['revenue']) ?? 0,
      avgFee: asDouble(today['avg_fee']) ?? 0,
      yesterdayRevenue: asDouble(j['yesterday_revenue']) ?? 0,
      recent: (j['recent'] as List? ?? []).map((e) => ReportRow.fromJson(e)).toList(),
    );
  }
}

class ReportRow {
  final int id;
  final String orderNo, customerName, status;
  final double deliveryFee;

  const ReportRow({
    required this.id,
    required this.orderNo,
    required this.customerName,
    required this.status,
    required this.deliveryFee,
  });

  factory ReportRow.fromJson(Map<String, dynamic> j) => ReportRow(
        id: j['id'],
        orderNo: j['order_no'] ?? '',
        customerName: j['customer_name'] ?? '',
        status: j['status'] ?? '',
        deliveryFee: asDouble(j['delivery_fee']) ?? 0,
      );
}
