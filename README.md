# CMNS Fix Mac - [ Website cmnsfixmac.com ]



เว็บไซต์สำหรับ [CMNS Fix Mac] ที่ให้บริการ [ซ่อมคอมพิวเตอร์, อุปกรณ์ Apple, และให้คำปรึกษา] โปรเจคนี้สร้างขึ้นเพื่อเป็นหน้าบ้านสำหรับธุรกิจ แสดงผลงาน, บริการ, และบทความที่เป็นประโยชน์ต่อลูกค้า

---

## 🚀 ฟีเจอร์เด็ด (Key Features)

* **หน้าแสดงบริการ (Services):** แสดงรายละเอียดบริการต่างๆ ที่ร้านมีให้
* **หน้าแสดงผลงาน (Works):** โชว์เคสงานซ่อมที่ผ่านมาเพื่อสร้างความน่าเชื่อถือ
* **หน้าสินค้า (Products):** แสดงรายการสินค้ามือสองหรืออุปกรณ์เสริมที่จำหน่าย
* **ระบบบทความ (Articles):** ให้ความรู้และ Tips & Tricks เกี่ยวกับการใช้งาน/ดูแลรักษาอุปกรณ์
* **เครื่องมือทดสอบออนไลน์ (Online Testers):**
    * ทดสอบกล้อง (Camera Tester)
    * ทดสอบคีย์บอร์ด (Keyboard Tester)
    * ทดสอบไมโครโฟน (Microphone Tester)
    * และอื่นๆ...
* **Responsive Design:** รองรับการแสดงผลบนทุกหน้าจอ ทั้ง Desktop, Tablet, และ Mobile

---

## 🛠️ เทคโนโลยีที่ใช้ (Tech Stack)

* **Backend:** PHP (Plain/Vanilla)
* **Frontend:** HTML5, CSS3, JavaScript
* **Database:** MySQL (via PDO)
* **Dependency Management:** Composer
* **Environment Variables:** phpdotenv

---

## ⚙️ วิธีติดตั้งและรันโปรเจค (Getting Started)

สำหรับนักพัฒนาที่สนใจ หรือตัวกูเองในอนาคตที่อาจจะลืม...

### Prerequisites

* PHP 7.4 หรือสูงกว่า
* MySQL Server
* Composer
* Git

### Installation

1.  **Clone the repository**
    ```bash
    git clone [https://github.com/k-zencool/cmnsfixmac.git](https://github.com/k-zencool/cmnsfixmac.git)
    ```
2.  **เข้าไปที่โฟลเดอร์โปรเจค**
    ```bash
    cd cmnsfixmac
    ```
3.  **ติดตั้ง Dependencies**
    ```bash
    composer install
    ```
4.  **ตั้งค่า Environment**
    * ก๊อปปี้ไฟล์ `.env.example` แล้วสร้างไฟล์ใหม่ชื่อ `.env`
        ```bash
        cp .env.example .env
        ```
    * เปิดไฟล์ `.env` ขึ้นมาแล้วใส่ข้อมูลการเชื่อมต่อ Database ของเครื่องคุณ
        ```
        DB_HOST=localhost
        DB_NAME=cmnsfixmac_db
        DB_USER=root
        DB_PASS=your_db_password
        ```
5.  **Import a database**
    * สร้าง Database เปล่าๆ ขึ้นมาใน MySQL Server
    * นำไฟล์ `.sql` (ถ้ามี) มา import เข้าไปใน Database ที่เพิ่งสร้าง

6.  **รันโปรเจค**
    * ชี้ web server ของคุณ (เช่น MAMP, XAMPP, Laragon) มาที่โฟลเดอร์โปรเจคนี้

---

## 🤝 ติดต่อ (Contact)

[ชื่อมึง] - [อีเมลมึง] - [ลิงก์โซเชียลอื่นๆ]

Project Link: [https://github.com/k-zencool/cmnsfixmac](https://github.com/k-zencool/cmnsfixmac)
