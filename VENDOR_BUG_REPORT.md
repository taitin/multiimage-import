# Bug Report: taitin/laravel-multiimage-import

## 📋 基本資訊

- **Package**: `taitin/laravel-multiimage-import`
- **Issue**: 檔案上傳衝突導致 `errno=21 Is a directory` 錯誤
- **Severity**: Critical（阻止檔案上傳功能正常運作）
- **Environment**: 
  - Laravel 8.83.29
  - PHP 8.1.29
  - dcat/laravel-admin 1.7.9

---

## 🐛 問題描述

### 主要問題

當使用 `ImportForm` 的 `multipleFile` 功能上傳檔案時，會發生以下錯誤：

```
ErrorException: stream_copy_to_stream(): read of 8192 bytes failed with errno=21 Is a directory
at vendor/league/flysystem/src/Adapter/Local.php:159
```

### 根本原因

**`ImportForm::setId()` 方法的設計缺陷**

```php
public function setId($value = 0)
{
    if (!empty($value)) {
        $this->id = $value;
        session(['import_id' => $this->id]);
    } else {
        $this->id = session('import_id', 0);  // ⚠️ 問題：預設為 0
    }
    return $this;
}
```

**問題分析：**

1. `form()` 方法中呼叫 `$this->setId(0)`
2. 因為 `!empty(0)` 為 `false`，所以執行 `else` 分支
3. 從 session 取得 `import_id`，若無則預設為 `0`
4. 導致所有使用者都使用 `import_temp/0/` 目錄
5. 多個使用者同時上傳時會產生檔案路徑衝突

---

## 🔍 重現步驟

1. 開啟匯入表單（ProductController、SupplierController 等）
2. 上傳 ZIP 檔案或多個圖檔
3. 錯誤發生：`errno=21 Is a directory`

**預期行為：** 每次上傳應使用唯一的 ID 建立獨立目錄

**實際行為：** 所有上傳都使用 `import_temp/0/` 導致衝突

---

## 💡 建議修正方案

### 方案 1：修正 `setId()` 邏輯（推薦）

```php
public function setId($value = 0)
{
    // 檢查是否為有效的非零值
    if ($value !== 0 && $value !== '0' && !empty($value)) {
        $this->id = $value;
        session(['import_id' => $this->id]);
    } else {
        // 總是生成新的唯一 ID
        $this->id = time() . substr(microtime(), 2, 6);
        session(['import_id' => $this->id]);
    }
    return $this;
}
```

### 方案 2：修正 `form()` 方法呼叫

```php
public function form()
{
    $this->sample_url = request()->input('sample_url');
    $this->html('<a target="_blank" href="' . $this->sample_url . '" class="btn btn-primary ml-1"><i class="feather icon-download"></i>' . __('multiimage-import::import.Download example') . '</a>');

    // ❌ 原始：$this->setId(0);
    // ✅ 修正：不傳參數或傳 null
    $this->setId();  // 或 $this->setId(null);
    
    $id = $this->id;
    // ... 其他程式碼
}
```

### 方案 3：增加 Session 清理機制

在 `form()` 方法開始時：

```php
public function form()
{
    // 清除可能存在的舊 session
    session()->forget('import_id');
    
    $this->sample_url = request()->input('sample_url');
    // ... 其他程式碼
}
```

---

## 🛠️ 我們的臨時解決方案

我們建立了 `ImportFormFix` 繼承原始 `ImportForm` 並覆寫有問題的方法：

```php
class ImportFormFix extends ImportForm
{
    public function setId($value = 0)
    {
        // 明確檢查 0 和 '0'
        if ($value !== 0 && $value !== '0' && !empty($value)) {
            $this->id = $value;
            session(['import_id' => $this->id]);
        } else {
            // 總是生成新的唯一 ID（時間戳記 + 微秒）
            $this->id = time() . substr(microtime(), 2, 6);
            session(['import_id' => $this->id]);
        }
        return $this;
    }

    public function form()
    {
        // 強制清除舊的 session
        session()->forget('import_id');
        
        // 永遠不要使用 0 作為 ID
        $uniqueId = time() . substr(microtime(), 2, 6);
        $this->setId($uniqueId);
        
        // ... 其他程式碼
    }
}
```

---

## 📊 影響範圍

- ✅ **圖檔上傳**：正常運作
- ❌ **ZIP 檔案上傳**：嚴重錯誤
- ❌ **多使用者同時上傳**：檔案路徑衝突
- ❌ **巢狀目錄結構**：未自動處理

---

## 🎯 額外建議

### 1. ZIP 檔案自動處理

目前 ZIP 解壓縮邏輯在 `handle()` 方法中，但建議增加：
- 自動偵測並處理巢狀目錄結構
- 提供更清晰的錯誤訊息

### 2. 目錄清理機制

建議增加自動清理機制：
```php
// 清理超過 24 小時的臨時目錄
protected function cleanOldDirectories()
{
    $basePath = public_path('storage/' . $this->import_path);
    $cutoffTime = time() - 86400;
    
    foreach (File::directories($basePath) as $dir) {
        if (is_numeric(basename($dir)) && intval(basename($dir)) < $cutoffTime) {
            File::deleteDirectory($dir);
        }
    }
}
```

---

## 📝 測試案例

### 測試 1：單一使用者上傳
- 上傳圖檔 → ✅ 成功
- 上傳 ZIP → ❌ 失敗（修正前）

### 測試 2：多使用者同時上傳
- 使用者 A 上傳 → ✅ 成功
- 使用者 B 同時上傳 → ❌ 衝突（修正前）

### 測試 3：巢狀 ZIP 結構
```
test.zip
└── 資料夾/
    ├── file1.jpg
    └── file2.jpg
```
- 需手動調整 ZIP 結構（目前）

---

## 🔗 相關資源

- **我們的修正分支**: `file-upload-fixes`
- **完整修正程式碼**: 
  - `app/Admin/Extensions/ImportFormFix.php`
  - `app/Admin/Extensions/MultiImageImportToolFix.php`
  - `app/Http/Middleware/CleanImportTemp.php`

---

## 📞 聯絡資訊

如需更多資訊或討論修正方案，請聯絡我們。

---

**報告日期**: 2025-12-05  
**報告者**: Minnow Development Team
