# ♠ POKER DOMAIN 德州撲克賽事計時器系統 (PHP NAS 版)

本專案結合了 **POKER DOMAIN 專業賽事電視看板（單場三欄流金黑/電光藍 & 雙場同螢幕分割）** 與 **FreePokerClock.com 簡約三圓環計時器**，採用純 PHP + JSON 檔案儲存架構，**免安裝資料庫（無須設定 MySQL / MariaDB）**，可直接部署於 Synology / QNAP 等各式 NAS。

---

## ✨ 功能特色

1. **三種顯示看板模式（即時多螢同步）**：
   - **單場豪華電視看板 (`display.php`)**：完整還原流金黑（Gold）、電光藍（Cyan）、經典紅（Red）、皇家綠（Emerald）霓虹發光風格，包含：
     - 左欄：賽事公告多頁自動輪播（`1/2`, `2/2`）與底部固定提示列
     - 中欄：當前盲注級別、進行/暫停狀態、超大發光倒數計時器、動態進度條、小盲/大盲、大盲前注、下一級盲注預告
     - 右欄：距離休息 / 距離賽事結束倒數（可自動判斷或點擊切換）、平均計分牌與平均大盲數 `(xxx.xbb)`、總獎池價值、當前/參賽人數
     - 右下角：`火焰 : 開 / 關` 動態背景粒子特效切換
   - **雙場賽事分割看板 (`dual.php`)**：單一電視同時左右分割顯示兩場不同主題配色的錦標賽（如左場流金黑、右場電光藍），獨立倒數與盲注資訊。
   - **FreePokerClock 簡約圓環看板 (`freeclock.php`)**：復刻 `freepokerclock.com` 經典黑紅三圓環進度條與快捷控制介面。

2. **遠端同步後台控台 (`admin.php`)**：
   - 支援工作人員以手機、平板或櫃檯電腦開啟 `admin.php` 遠端控制，現場大電視開啟 `display.php` 或 `dual.php` 自動秒級同步。
   - 內建一鍵 **+1 新參賽買入**、**-1 玩家淘汰**、**+1 Rebuy**、**+1 Add-on**，自動即時換算平均計分牌、平均 BB 數與總獎池。
   - 內建 **盲注結構產生器**、中場休息（Break）插入、隨時跳轉級別或微調時間（`+1分` / `-1分` / `+10秒` / `-10秒`）。

3. **內建音效與中文語音播報**：
   - 使用瀏覽器原生 Web Audio API 合成 **最後 1 分鐘提醒雙響音**、**最後 5 秒倒數提示音**、**升盲和弦鐘聲**，並支援開啟 **中文語音播報 (TTS)**，完全無需外掛 MP3 檔案。

4. **電視畫面快捷鍵與自動隱藏懸浮控制列**：
   - 滑鼠移動時自動浮現底部快捷控制列（閒置 3 秒自動隱藏）。
   - 支援鍵盤操作：
     - `Space (空白鍵)`：開始 / 暫停計時
     - `←` / `→`：上一級 / 下一級盲注
     - `↑` / `↓`：+1 參賽買入 / -1 玩家淘汰
     - `F`：切換全螢幕
     - `M`：切換靜音

---

## 🚀 NAS 安裝與部署方式

### 方式一：Synology / QNAP 內建 Web Station (最簡單，直接放資料夾)

1. 在 NAS 開啟 **Web Station**（Synology）或 **Web Server**（QNAP），確認已啟用 PHP（支援 PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3，無須額外擴充套件）。
2. 將本專案所有檔案上傳至 NAS 的網頁共用資料夾，例如：`/web/pokerclock/`。
3. 確認 `data/` 資料夾具備讀寫權限（系統會自動建立 `data/state.json` 儲存賽事狀態；若在 Linux/NAS 上權限不足，可將 `data` 資料夾權限設為 `777` 或讓 `http` / `www-data` 群組可寫入）。
4. 打開瀏覽器輸入：
   - 總覽首頁：`http://您的NAS_IP/pokerclock/`
   - 單場電視看板：`http://您的NAS_IP/pokerclock/display.php`
   - 雙場分割看板：`http://您的NAS_IP/pokerclock/dual.php`
   - 後台管理控台：`http://您的NAS_IP/pokerclock/admin.php`

### 方式二：使用 NAS Docker (Container Manager / Container Station)

若您偏好在 NAS 使用 Docker 部署：

```bash
docker compose up -d --build
```

啟動後直接瀏覽 `http://您的NAS_IP:8088` 即可使用，所有賽事設定與狀態會自動持久化保存於 `./data/state.json`。
