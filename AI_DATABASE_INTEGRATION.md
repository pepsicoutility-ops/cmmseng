# AI Chat - Database Integration

## 🎯 Overview

AI Assistant sekarang terhubung dengan database CMMS dan dapat memberikan informasi real-time tentang:
- Equipment details (lokasi, spesifikasi, status)
- Maintenance history (PM, Work Orders)
- Equipment troubles/issues
- Running hours
- Active work orders

## 🛠️ Available AI Functions

### 1. **get_equipment_info**
Mendapatkan informasi detail equipment

**Contoh pertanyaan:**
- "Tampilkan info Processing Unit"
- "Cari equipment dengan kode AST-EXT-001"
- "Dimana lokasi Extruder?"

**Response meliputi:**
- Nama & kode equipment
- Model & serial number
- Tanggal instalasi
- Lokasi (Area & Sub Area)
- Status aktif/tidak

### 2. **get_equipment_history**
Melihat riwayat maintenance dalam N hari terakhir

**Contoh pertanyaan:**
- "Tampilkan history maintenance Processing Unit 30 hari terakhir"
- "Apa saja PM yang dilakukan di Extruder bulan ini?"
- "History perbaikan Cooling System"

**Response meliputi:**
- Daftar Preventive Maintenance
- Daftar Work Orders
- Summary total PM & WO

### 3. **get_active_work_orders**
Mendapatkan daftar work order yang sedang berjalan

**Contoh pertanyaan:**
- "Tampilkan work order yang aktif"
- "WO apa saja yang sedang open?"
- "Daftar WO dengan priority high"

**Response meliputi:**
- WO Number
- Equipment & component
- Type, description, priority
- Status & tanggal created

### 4. **get_equipment_troubles**
Melihat riwayat trouble/masalah equipment

**Contoh pertanyaan:**
- "Masalah apa yang pernah terjadi di Extruder?"
- "Tampilkan trouble history Cooling System"
- "Daftar issue equipment critical"

**Response meliputi:**
- Title & deskripsi issue
- Equipment & component
- Priority & status
- Resolution notes
- Downtime (menit)

### 5. **get_running_hours**
Mendapatkan data running hours equipment

**Contoh pertanyaan:**
- "Berapa running hours Processing Unit?"
- "Tampilkan data operasi Extruder"
- "Average running hours per hari"

**Response meliputi:**
- Latest reading
- Total hours 30 hari
- Average per hari
- History readings

### 6. **get_checklist_data** 🆕
Mendapatkan data checklist untuk equipment (Compressor 1, Compressor 2, Chiller 1, Chiller 2, AHU)

**Contoh pertanyaan:**
- "Tampilkan data checklist Compressor 1 terakhir"
- "Berapa run hours Chiller 1?"
- "Data checklist shift 1 untuk Compressor 2"
- "Status parameter Chiller 2 hari ini"

**Response meliputi:**
- Data checklist terakhir (shift, operator, tanggal)
- Parameter operasional (temperature, pressure, run hours, dll)
- Recent records (5 data terakhir)
- Summary per record

**Equipment yang didukung:**
- Compressor 1: bearing oil temp/pressure, discharge temp/pressure, CWS/CWR, refrigerant pressure
- Compressor 2: sama seperti Compressor 1
- Chiller 1: evap/condenser pressure, motor amps/volts, oil level, temperature
- Chiller 2: sama seperti Chiller 1
- AHU: temperature, pressure, run hours

## 📝 Cara Menggunakan

### 1. Buka AI Chat
```
http://cmmseng.test/pep/chat-ai
```

### 2. Tanyakan dengan Bahasa Natural
AI akan otomatis memanggil function yang tepat berdasarkan pertanyaan Anda.

**Contoh Percakapan:**

```
User: "Tampilkan info Processing Unit"
AI: [Memanggil get_equipment_info("Processing Unit")]
    Processing Unit (AST-PROC-001)
    - Model: PRO-500X
    - Serial: SN-PROC-2024-001
    - Lokasi: Proses > EP (Extrusion Process)
    - Instalasi: 15-01-2024
    - Status: Active

User: "Ada masalah apa saja di equipment ini?"
AI: [Memanggil get_equipment_troubles("Processing Unit")]
    Ditemukan 1 trouble:
    1. asas (Medium Priority) - Resolved
       - Issue: asasas
       - Downtime: -48 menit
       - Tanggal: 21-12-2025 16:31

User: "Berapa running hours nya?"
AI: [Memanggil get_running_hours("Processing Unit")]
    Running Hours Processing Unit:
    - Latest: [data terakhir]
    - Total 30 hari: [total]
    - Average/hari: [average]
```

## 🔧 Technical Implementation

### AIToolsService.php
Service yang menyediakan function definitions dan executor untuk AI.

```php
AIToolsService::getToolDefinitions() // Daftar function untuk AI
AIToolsService::executeTool($name, $args) // Execute function
```

### ChatAIService.php
Updated untuk mendukung function calling:
- Menambahkan `tools` & `tool_choice` di payload
- Handle tool_calls dari AI response
- Memanggil AIToolsService untuk execute function
- Mengirim hasil ke AI untuk generate response final

### System Prompt
AI dikonfigurasi dengan context:
- PEP Engineering AI Assistant
- Fokus pada CMMS & engineering equipment
- Response dalam Bahasa Indonesia
- Tidak menggunakan LaTeX/format kode

## 🚀 Next Steps

### Potential Enhancements:
1. **Sensor Data Integration** - Real-time sensor readings
2. **Predictive Analytics** - ML predictions untuk failure
3. **Recommendations** - AI-generated maintenance suggestions
4. **Report Generation** - Auto-generate maintenance reports
5. **Image Analysis** - Analyze equipment photos
6. **Voice Commands** - Voice-to-text untuk mobile

### Additional Functions:
- `get_spare_parts_stock` - Cek stok spare parts
- `get_pm_schedule` - Lihat jadwal PM mendatang
- `get_cost_analysis` - Analisa biaya maintenance
- `get_technician_availability` - Jadwal teknisi

## 📊 Usage Statistics

AI akan log setiap function call untuk analytics:
- Function yang paling sering digunakan
- Equipment yang paling banyak dicari
- Response time
- Success rate

## 🔒 Security

- Function hanya bisa akses data read-only
- User authentication required
- Logging semua AI interactions
- No direct database modification

## 💰 Cost Optimization

**Model: GPT-4o-mini**
- ~$0.15 per 1M input tokens
- ~$0.60 per 1M output tokens
- Function calling menambah token usage
- Estimated: ~$0.01 per conversation

**Tips:**
- Gunakan model GPT-4o-mini untuk daily use
- GPT-4o untuk complex analysis
- Set max_tokens untuk limit cost
- Monitor usage di SumoPod dashboard

---

**Status**: ✅ Production Ready  
**Version**: v1.0  
**Last Update**: 23 December 2025
