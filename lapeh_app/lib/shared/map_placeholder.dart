import 'package:flutter/material.dart';
import '../core/theme.dart';

/// A stylized fake map drawn with CustomPaint — looks map-like without
/// any Google Maps API key. Replace with google_maps_flutter when wiring keys.
class MapPlaceholder extends StatelessWidget {
  final double height;
  final bool showRoute;
  final double? movingDotT; // 0..1 position along route, null to hide
  final String pickupLabel;
  final String dropLabel;
  final BorderRadius? radius;

  const MapPlaceholder({
    super.key,
    this.height = 240,
    this.showRoute = true,
    this.movingDotT,
    this.pickupLabel = 'Pickup',
    this.dropLabel = 'Customer',
    this.radius,
  });

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: radius ?? BorderRadius.circular(16),
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: Stack(
          children: [
            Positioned.fill(
              child: CustomPaint(
                painter: _MapPainter(showRoute: showRoute, movingDotT: movingDotT),
              ),
            ),
            Positioned(
              left: 10,
              bottom: 12,
              child: _MapTag(icon: Icons.storefront_outlined, label: pickupLabel),
            ),
            if (dropLabel.isNotEmpty)
              Positioned(
                right: 10,
                top: 12,
                child: _MapTag(icon: Icons.location_on_outlined, label: dropLabel, accent: true),
              ),
          ],
        ),
      ),
    );
  }
}

class _MapTag extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool accent;
  const _MapTag({required this.icon, required this.label, this.accent = false});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(9),
        boxShadow: const [BoxShadow(color: Color(0x22000000), blurRadius: 10, offset: Offset(0, 4))],
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 13, color: accent ? AppColors.pinkDeep : AppColors.pink),
        const SizedBox(width: 5),
        Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: accent ? AppColors.pinkDeep : AppColors.ink)),
      ]),
    );
  }
}

class _MapPainter extends CustomPainter {
  final bool showRoute;
  final double? movingDotT;
  _MapPainter({required this.showRoute, this.movingDotT});

  @override
  void paint(Canvas canvas, Size size) {
    final w = size.width, h = size.height;
    canvas.drawRect(Offset.zero & size, Paint()..color = const Color(0xFFEAEFEA));

    // city blocks
    final block = Paint()..color = const Color(0xFFDFE7E0);
    final blocks = [
      Rect.fromLTWH(w * .04, h * .06, w * .24, h * .2),
      Rect.fromLTWH(w * .34, h * .04, w * .2, h * .16),
      Rect.fromLTWH(w * .62, h * .07, w * .3, h * .24),
      Rect.fromLTWH(w * .06, h * .36, w * .2, h * .24),
      Rect.fromLTWH(w * .4, h * .34, w * .22, h * .18),
      Rect.fromLTWH(w * .7, h * .4, w * .24, h * .22),
      Rect.fromLTWH(w * .05, h * .7, w * .27, h * .23),
      Rect.fromLTWH(w * .4, h * .64, w * .2, h * .29),
      Rect.fromLTWH(w * .7, h * .71, w * .25, h * .22),
    ];
    for (final b in blocks) {
      canvas.drawRRect(RRect.fromRectAndRadius(b, const Radius.circular(4)), block);
    }

    // roads
    final road = Paint()
      ..color = Colors.white
      ..strokeWidth = 7
      ..style = PaintingStyle.stroke;
    canvas.drawLine(Offset(0, h * .32), Offset(w, h * .32), road);
    canvas.drawLine(Offset(0, h * .66), Offset(w, h * .66), road);
    canvas.drawLine(Offset(w * .3, 0), Offset(w * .3, h), road);
    canvas.drawLine(Offset(w * .64, 0), Offset(w * .64, h), road);

    // green strip
    canvas.drawLine(
      Offset(0, h * .5), Offset(w, h * .5),
      Paint()
        ..color = const Color(0xFF9FE3C1)
        ..strokeWidth = 3,
    );

    final pickup = Offset(w * .17, h * .84);
    final drop = Offset(w * .83, h * .16);

    if (showRoute) {
      final path = Path()
        ..moveTo(pickup.dx, pickup.dy)
        ..cubicTo(w * .3, h * .6, w * .45, h * .6, w * .5, h * .5)
        ..cubicTo(w * .6, h * .35, w * .75, h * .28, drop.dx, drop.dy);
      final dashed = Paint()
        ..color = AppColors.pink
        ..strokeWidth = 5
        ..style = PaintingStyle.stroke
        ..strokeCap = StrokeCap.round;
      _drawDashed(canvas, path, dashed);

      if (movingDotT != null) {
        final metrics = path.computeMetrics().toList();
        if (metrics.isNotEmpty) {
          final m = metrics.first;
          final pos = m.getTangentForOffset(m.length * movingDotT!.clamp(0, 1))?.position;
          if (pos != null) {
            canvas.drawCircle(pos, 13, Paint()..color = AppColors.pink.withValues(alpha: 0.18));
            canvas.drawCircle(pos, 7, Paint()..color = AppColors.pink);
            canvas.drawCircle(pos, 7, Paint()..color = Colors.white..style = PaintingStyle.stroke..strokeWidth = 3);
          }
        }
      }
    }

    // pickup marker (dark dot)
    canvas.drawCircle(pickup, 9, Paint()..color = AppColors.ink);
    canvas.drawCircle(pickup, 3.4, Paint()..color = Colors.white);

    // drop marker (pink pin)
    canvas.drawCircle(drop, 9, Paint()..color = AppColors.pink);
    canvas.drawCircle(drop, 3.6, Paint()..color = Colors.white);
  }

  void _drawDashed(Canvas canvas, Path path, Paint paint) {
    const dash = 2.0, gap = 9.0;
    for (final metric in path.computeMetrics()) {
      double dist = 0;
      while (dist < metric.length) {
        final next = dist + dash;
        canvas.drawPath(metric.extractPath(dist, next), paint);
        dist = next + gap;
      }
    }
  }

  @override
  bool shouldRepaint(covariant _MapPainter old) => old.movingDotT != movingDotT;
}
