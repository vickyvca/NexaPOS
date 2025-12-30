# POST Create New Master Barang (Endpoint Final)
POST http://localhost/MODESTOK/mdcpos/api_master_barang.php HTTP/1.1
Content-Type: application/json

{
    "KODEBRG": "BRG-NEW-{{$randomInt 100 999}}",
    "NAMABRG": "Produk Baru dari Web",
    "KODESP": "SP1", 
    "KODEJN": "01", 
    "KODEMR": "MR1", 
    "KODEST": "ST1",
    "HGBELI": 120000.00,
    "HGJUAL": 180000.00,
    "DISC": 0.0,
    "MARKUP": 0.5,
    "TGLBELI": "{{$datetime iso8601}}",
    "CUSER": "WEB_ADMIN",
    "CKOMP": "WEB_SERVER",
    "STOKAWAL": 50,
    "STOKMIN": 5,
    "STOKMAX": 200,
    "ST00": 30,
    "ST01": 5,
    "ST02": 5,
    "ST03": 5,
    "ST04": 5
}

###