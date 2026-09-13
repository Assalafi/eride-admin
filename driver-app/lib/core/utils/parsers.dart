// Safe conversions for loosely typed API payloads.

/// Returns a copy of [value] as a `Map<String, dynamic>`, or an empty map.
Map<String, dynamic> asMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

/// Returns [value] as a list, unwrapping `{data: [...]}` envelopes.
List<dynamic> asList(dynamic value) => value is List
    ? value
    : value is Map && value['data'] is List
        ? List<dynamic>.from(value['data'])
        : <dynamic>[];

/// Returns [value] as a [double], falling back to `0`.
double asDouble(dynamic value) => value is num
    ? value.toDouble()
    : double.tryParse(value?.toString() ?? '') ?? 0;
