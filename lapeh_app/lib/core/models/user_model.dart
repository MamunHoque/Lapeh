import 'order_model.dart' show asDouble;

class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String role; // admin | sender | driver
  final String status;
  final String locale;
  final String? avatar;
  final bool phoneVerified;
  final DriverProfile? driver;
  final SenderProfile? sender;

  const UserModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    required this.role,
    required this.status,
    required this.locale,
    this.avatar,
    this.phoneVerified = false,
    this.driver,
    this.sender,
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
        phoneVerified: j['phone_verified'] ?? false,
        driver: j['driver'] != null ? DriverProfile.fromJson(j['driver']) : null,
        sender: j['sender'] != null ? SenderProfile.fromJson(j['sender']) : null,
      );

  UserModel copyWith({String? locale, bool? phoneVerified, SenderProfile? sender}) => UserModel(
        id: id,
        name: name,
        phone: phone,
        email: email,
        role: role,
        status: status,
        locale: locale ?? this.locale,
        avatar: avatar,
        phoneVerified: phoneVerified ?? this.phoneVerified,
        driver: driver,
        sender: sender ?? this.sender,
      );

  bool get isDriver => role == 'driver';
  bool get isSender => role == 'sender';
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

class SenderProfile {
  final int id;
  final String type; // individual | business
  final String? businessName;
  final String? businessCategory;
  final String? contactPersonName;
  final String? defaultPickupAddress;
  final double? defaultPickupLat;
  final double? defaultPickupLng;
  final String status;

  const SenderProfile({
    required this.id,
    required this.type,
    this.businessName,
    this.businessCategory,
    this.contactPersonName,
    this.defaultPickupAddress,
    this.defaultPickupLat,
    this.defaultPickupLng,
    required this.status,
  });

  bool get isBusiness => type == 'business';

  factory SenderProfile.fromJson(Map<String, dynamic> j) => SenderProfile(
        id: j['id'],
        type: j['type'] ?? 'individual',
        businessName: j['business_name'],
        businessCategory: j['business_category'],
        contactPersonName: j['contact_person_name'],
        defaultPickupAddress: j['default_pickup_address'],
        defaultPickupLat: asDouble(j['default_pickup_lat']),
        defaultPickupLng: asDouble(j['default_pickup_lng']),
        status: j['status'] ?? 'pending',
      );
}
