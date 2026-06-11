import 'order_model.dart' show asDouble, asInt;

/// Aggregated earnings for a single time window (today / week / month / all-time).
class EarningsPeriod {
  final double earnings; // net take-home (after commission)
  final double commission;
  final int trips;
  final double avgEarning;
  final double distanceKm;

  const EarningsPeriod({
    this.earnings = 0,
    this.commission = 0,
    this.trips = 0,
    this.avgEarning = 0,
    this.distanceKm = 0,
  });

  factory EarningsPeriod.fromJson(Map<String, dynamic>? j) {
    j ??= const {};
    return EarningsPeriod(
      earnings: asDouble(j['earnings']) ?? 0,
      commission: asDouble(j['commission']) ?? 0,
      trips: asInt(j['trips']) ?? 0,
      avgEarning: asDouble(j['avg_earning']) ?? 0,
      distanceKm: asDouble(j['distance_km']) ?? 0,
    );
  }
}

/// One day in the 7-day breakdown bar chart.
class DailyEarning {
  final String date; // yyyy-MM-dd
  final double earnings;
  final int trips;

  const DailyEarning({required this.date, required this.earnings, required this.trips});

  factory DailyEarning.fromJson(Map<String, dynamic> j) => DailyEarning(
        date: j['date'] ?? '',
        earnings: asDouble(j['earnings']) ?? 0,
        trips: asInt(j['trips']) ?? 0,
      );

  DateTime? get dateTime => DateTime.tryParse(date);
}

/// A single delivered trip in the history list.
class TripEarning {
  final String orderNo;
  final String sender;
  final String area;
  final double earning; // net payout after commission
  final double commission;
  final double? distanceKm;
  final DateTime? deliveredAt;

  const TripEarning({
    required this.orderNo,
    required this.sender,
    required this.area,
    required this.earning,
    this.commission = 0,
    this.distanceKm,
    this.deliveredAt,
  });

  factory TripEarning.fromJson(Map<String, dynamic> j) => TripEarning(
        orderNo: j['order_no'] ?? '–',
        sender: j['sender'] ?? '',
        area: j['area'] ?? '',
        earning: asDouble(j['earning']) ?? 0,
        commission: asDouble(j['commission']) ?? 0,
        distanceKm: asDouble(j['distance_km']),
        deliveredAt: j['delivered_at'] != null ? DateTime.tryParse(j['delivered_at'].toString()) : null,
      );
}

/// Full driver earnings payload from GET /api/driver/earnings.
class DriverEarningsData {
  final EarningsPeriod today;
  final EarningsPeriod week;
  final EarningsPeriod month;
  final EarningsPeriod allTime;
  final double yesterdayEarnings;
  final List<DailyEarning> dailyBreakdown;
  final List<TripEarning> history;

  const DriverEarningsData({
    required this.today,
    required this.week,
    required this.month,
    required this.allTime,
    required this.yesterdayEarnings,
    required this.dailyBreakdown,
    required this.history,
  });

  /// Percent change in today's earnings vs yesterday; null when no baseline.
  double? get todayDeltaPct {
    if (yesterdayEarnings <= 0) return null;
    return (today.earnings - yesterdayEarnings) / yesterdayEarnings * 100;
  }

  /// Highest-earning day in the breakdown, or null when nothing was earned.
  DailyEarning? get bestDay {
    if (dailyBreakdown.isEmpty) return null;
    final best = dailyBreakdown.reduce((a, b) => b.earnings > a.earnings ? b : a);
    return best.earnings > 0 ? best : null;
  }

  factory DriverEarningsData.fromJson(Map<String, dynamic> j) {
    final hist = j['history'];
    final List<dynamic> rows =
        hist is Map ? (hist['data'] as List? ?? const []) : (hist as List? ?? const []);
    return DriverEarningsData(
      today: EarningsPeriod.fromJson(j['today'] as Map<String, dynamic>?),
      week: EarningsPeriod.fromJson(j['week'] as Map<String, dynamic>?),
      month: EarningsPeriod.fromJson(j['month'] as Map<String, dynamic>?),
      allTime: EarningsPeriod.fromJson(j['all_time'] as Map<String, dynamic>?),
      yesterdayEarnings: asDouble(j['yesterday_earnings']) ?? 0,
      dailyBreakdown: (j['daily_breakdown'] as List? ?? const [])
          .map((e) => DailyEarning.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
      history: rows.map((e) => TripEarning.fromJson(Map<String, dynamic>.from(e as Map))).toList(),
    );
  }
}
