# control_acceso_app.py
# Aplicación de control de accesos para autos y motos

import os
import sqlite3
import qrcode
import cv2
from pyzbar import pyzbar
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from PIL import Image, ImageTk
from datetime import datetime

# --- Configuración de la base de datos ---
DB_PATH = 'access_control.db'

conn = sqlite3.connect(DB_PATH)
cursor = conn.cursor()

# Crear tablas si no existen
cursor.execute('''
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    vehicle_type TEXT NOT NULL,
    plate TEXT NOT NULL UNIQUE,
    qr_path TEXT NOT NULL
)''')

cursor.execute('''
CREATE TABLE IF NOT EXISTS access_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    timestamp TEXT NOT NULL,
    event TEXT NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id)
)''')

conn.commit()

# --- Funciones auxiliares ---

def generate_qr(data: str, save_dir='qrcodes') -> str:
    os.makedirs(save_dir, exist_ok=True)
    filename = os.path.join(save_dir, f"{data}.png")
    qr = qrcode.QRCode(version=1, box_size=10, border=4)
    qr.add_data(data)
    qr.make(fit=True)
    img = qr.make_image(fill='black', back_color='white')
    img.save(filename)
    return filename


def register_user(name, vehicle_type, plate):
    # Generar un código QR único (usamos la placa como identificador)
    qr_path = generate_qr(plate)
    try:
        cursor.execute('INSERT INTO users (name, vehicle_type, plate, qr_path) VALUES (?, ?, ?, ?)',
                       (name, vehicle_type, plate, qr_path))
        conn.commit()
        return True, qr_path
    except sqlite3.IntegrityError:
        return False, None


def log_event(user_id, event):
    ts = datetime.now().isoformat()
    cursor.execute('INSERT INTO access_log (user_id, timestamp, event) VALUES (?, ?, ?)',
                   (user_id, ts, event))
    conn.commit()


def scan_qr_from_camera():
    cap = cv2.VideoCapture(0)
    found = set()
    messagebox.showinfo("Escaneo QR", "Se iniciará el escaneo. Presiona 'q' para salir.")

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        barcodes = pyzbar.decode(frame)
        for barcode in barcodes:
            plate = barcode.data.decode('utf-8')
            if plate not in found:
                found.add(plate)
                cursor.execute('SELECT id, name, vehicle_type FROM users WHERE plate = ?', (plate,))
                user = cursor.fetchone()
                if user:
                    user_id, name, vehicle_type = user
                    # Determinar si es entrada o salida
                    # Buscamos último evento
                    cursor.execute('SELECT event FROM access_log WHERE user_id = ? ORDER BY id DESC LIMIT 1', (user_id,))
                    last = cursor.fetchone()
                    event = 'entrada' if not last or last[0] == 'salida' else 'salida'
                    log_event(user_id, event)
                    messagebox.showinfo("Acceso", f"{event.capitalize()} registrada para {name} ({vehicle_type})")
                else:
                    messagebox.showwarning("Desconocido", f"Placa {plate} no registrada.")
        cv2.imshow('Escaneo QR - Presiona q para salir', frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()

# --- Interfaz gráfica ---
class AccessControlApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("Control de Accesos")
        self.geometry('400x300')

        tab_control = ttk.Notebook(self)
        self.register_tab = ttk.Frame(tab_control)
        self.scan_tab = ttk.Frame(tab_control)
        tab_control.add(self.register_tab, text='Registro')
        tab_control.add(self.scan_tab, text='Reconocimiento')
        tab_control.pack(expand=1, fill='both')

        self.create_register_tab()
        self.create_scan_tab()

    def create_register_tab(self):
        lbl_name = ttk.Label(self.register_tab, text='Nombre completo:')
        lbl_name.pack(pady=5)
        self.entry_name = ttk.Entry(self.register_tab)
        self.entry_name.pack(pady=5)

        lbl_vehicle = ttk.Label(self.register_tab, text='Tipo de vehículo:')
        lbl_vehicle.pack(pady=5)
        self.entry_vehicle = ttk.Entry(self.register_tab)
        self.entry_vehicle.pack(pady=5)

        lbl_plate = ttk.Label(self.register_tab, text='Placa:')
        lbl_plate.pack(pady=5)
        self.entry_plate = ttk.Entry(self.register_tab)
        self.entry_plate.pack(pady=5)

        btn_register = ttk.Button(self.register_tab, text='Registrar', command=self.handle_register)
        btn_register.pack(pady=10)

        self.lbl_qr = ttk.Label(self.register_tab)
        self.lbl_qr.pack(pady=10)

    def handle_register(self):
        name = self.entry_name.get().strip()
        vehicle = self.entry_vehicle.get().strip()
        plate = self.entry_plate.get().strip()
        if not name or not vehicle or not plate:
            messagebox.showwarning("Campos vacíos", "Por favor completa todos los campos.")
            return

        success, qr_path = register_user(name, vehicle, plate)
        if success:
            img = Image.open(qr_path)
            img = img.resize((150, 150))
            photo = ImageTk.PhotoImage(img)
            self.lbl_qr.configure(image=photo)
            self.lbl_qr.image = photo
            messagebox.showinfo("Registrado", f"Usuario {name} registrado correctamente.")
        else:
            messagebox.showerror("Error", "Ya existe un registro con esa placa.")

    def create_scan_tab(self):
        lbl = ttk.Label(self.scan_tab, text='Escaneo de códigos QR con cámara')
        lbl.pack(pady=20)
        btn_scan = ttk.Button(self.scan_tab, text='Iniciar Escaneo', command=scan_qr_from_camera)
        btn_scan.pack(pady=10)

if __name__ == '__main__':
    app = AccessControlApp()
    app.mainloop()
