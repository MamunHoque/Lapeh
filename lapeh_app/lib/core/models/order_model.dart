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

  // Restaurant info (populated for driver order payloads)
  final String? restaurantName;
  final double? restaurantLat;
  final double? restaurantLng;
  final String? restaurantAddress;
  final String? restaurantPhone;

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
    this.restaurantName,
    this.restaurantLat,
    this.restaurantLng,
    this.restaurantAddress,
    this.restaurantPhone,
  });

  factory OrderModel.fromJson(Map<String, dynamic> j) {
    // Support both nested restaurant object and flat restaurant_* fields
    final rest = j['restaurant'] as Map<String, dynamic>?;
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
      restaurantName: rest?['name'] ?? j['restaurant_name'],
      restaurantLat: asDouble(rest?['lat'] ?? j['restaurant_lat']),
      restaurantLng: asDouble(rest?['lng'] ?? j['restaurant_lng']),
      restaurantAddress: rest?['address'] ?? j['restaurant_address'],
      restaurantPhone: rest?['phone'] ?? j['restaurant_phone'],
    );
  }

  bool get isTerminal => status == 'delivered' || status == 'cancelled';
  bool get hasDriver => driver != null;
  bool get canRate => status == 'delivered' && hasDriver;
  bool get hasRestaurantCoords => restaurantLat != null && restaurantLng != null;
  bool get hasCustomerCoords => customerLat != null && customerLng != null;
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
  final String restaurantName;
  final double restaurantLat;
  final double restaurantLng;
  final String restaurantAddress;
  final double? deliveryFee;
  final double? distanceKm;
  final int timeoutSec;

  const DeliveryOffer({
    required this.id,
    required this.orderNo,
    required this.restaurantName,
    required this.restaurantLat,
    required this.restaurantLng,
    required this.restaurantAddress,
    this.deliveryFee,
    this.distanceKm,
    required this.timeoutSec,
  });

  factory DeliveryOffer.fromJson(Map<String, dynamic> j) => DeliveryOffer(
        id: j['id'],
        orderNo: j['order_no'],
        restaurantName: j['restaurant_name'],
        restaurantLat: asDouble(j['restaurant_lat']) ?? 0,
        restaurantLng: asDouble(j['restaurant_lng']) ?? 0,
        restaurantAddress: j['restaurant_address'],
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
