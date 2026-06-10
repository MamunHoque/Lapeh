import 'order_model.dart' show asDouble;

class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String role; // admin | restaurant | driver
  final String status;
  final String locale;
  final String? avatar;
  final DriverProfile? driver;
  final RestaurantProfile? restaurant;

  const UserModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    required this.role,
    required this.status,
    required this.locale,
    this.avatar,
    this.driver,
    this.restaurant,
  });

  factory UserModel.fromJson(Map<String, dynamic> j) => UserModel(
        id: j['id'],
        name: j['name'],
        phone: j['phone'],
        email: j['email'],
        role: j['role'],
        status: j['status'],
        locale: j['locale'] ?? 'en',
        avatar: j['avatar'],
        driver: j['driver'] != null ? DriverProfile.fromJson(j['driver']) : null,
        restaurant: j['restaurant'] != null ? RestaurantProfile.fromJson(j['restaurant']) : null,
      );

  bool get isDriver => role == 'driver';
  bool get isRestaurant => role == 'restaurant';
  bool get isAdmin => role == 'admin';
}

class DriverProfile {
  final int id;
  final String status;
  final String vehicleType;
  final String? vehiclePlate;
  final double ratingAvg;
  final bool isVerified;

  const DriverProfile({
    required this.id,
    required this.status,
    required this.vehicleType,
    this.vehiclePlate,
    required this.ratingAvg,
    required this.isVerified,
  });

  factory DriverProfile.fromJson(Map<String, dynamic> j) => DriverProfile(
        id: j['id'],
        status: j['status'],
        vehicleType: j['vehicle_type'],
        vehiclePlate: j['vehicle_plate'],
        ratingAvg: asDouble(j['rating_avg']) ?? 0,
        isVerified: j['is_verified'] ?? false,
      );
}

class RestaurantProfile {
  final int id;
  final String name;
  final String? logo;

  const RestaurantProfile({required this.id, required this.name, this.logo});

  factory RestaurantProfile.fromJson(Map<String, dynamic> j) =>
      RestaurantProfile(id: j['id'], name: j['name'], logo: j['logo']);
}
