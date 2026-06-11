import '../api_client.dart';

/// Support contact channels + localized FAQ from GET /api/meta.
class SupportInfo {
  final String? phone;
  final String? email;
  final String? whatsapp;
  final List<FaqItem> faq;

  const SupportInfo({this.phone, this.email, this.whatsapp, this.faq = const []});

  factory SupportInfo.fromJson(Map<String, dynamic> j) => SupportInfo(
        phone: j['phone'],
        email: j['email'],
        whatsapp: j['whatsapp'],
        faq: (j['faq'] as List? ?? const [])
            .map((e) => FaqItem.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}

class FaqItem {
  final String qEn, qAr, aEn, aAr;
  const FaqItem({required this.qEn, required this.qAr, required this.aEn, required this.aAr});

  factory FaqItem.fromJson(Map<String, dynamic> j) => FaqItem(
        qEn: j['q_en'] ?? '',
        qAr: j['q_ar'] ?? '',
        aEn: j['a_en'] ?? '',
        aAr: j['a_ar'] ?? '',
      );

  String question(bool arabic) => arabic ? qAr : qEn;
  String answer(bool arabic) => arabic ? aAr : aEn;
}

class MetaService {
  final _api = ApiClient();

  Future<SupportInfo> support() async {
    final res = await _api.dio.get('/meta');
    final s = res.data['support'];
    return s is Map ? SupportInfo.fromJson(Map<String, dynamic>.from(s)) : const SupportInfo();
  }
}
