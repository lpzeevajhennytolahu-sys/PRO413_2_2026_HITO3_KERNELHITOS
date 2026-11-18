#!/usr/bin/env python3
"""Importa Inventarios 2026 (1).xlsx a thaki_modificado (producto + unidades + precios + REF)."""

from __future__ import annotations

import json
import re
import sys
from collections import defaultdict
from datetime import date
from pathlib import Path

import openpyxl

XLSX = Path(r"c:\Users\Andres\Downloads\Inventarios 2026 (1).xlsx")
OUT_SQL = Path(__file__).resolve().parents[1] / "scripts" / "inventario_excel_import.sql"

CLASE_RULES = [
    (r"BOTELL[OÓ]N.*OX[IÍ]GENO|OX[IÍ]GENO", "CL-001"),
    (r"BOTELLA.*AGUA|BOTELLA DE AGUA", "CL-002"),
    (r"BICICLETA|BCICLETA", "CL-003"),
    (r"CARPA", "CL-004"),
    (r"COLCHONETA", "CL-005"),
    (r"CABINA|DUCHA", "CL-006"),
    (r"C[AÁ]MARA HIPERB[AÁ]RICA|HIPERBARICA", "CL-007"),
    (r"MESA", "CL-008"),
    (r"SILLA|SENTADERO", "CL-009"),
    (r"SLEEPING|SLIPING|SLIPING|SACO DE DORMIR|BOLSA DE DORMIR", "CL-010"),
    (r"PALA", "CL-012"),
    (r"M[AÁ]SCARA", "CL-013"),
    (r"CONTENEDOR|BALDE", "CL-014"),
    (r"TEL[EÉ]FONO|STARLING", "CL-015"),
    (r"CALENTADOR", "CL-016"),
    (r"MAN[OÓ]METRO", "CL-017"),
    (r"CALDERA", "CL-019"),
    (r"OLLA", "CL-020"),
    (r"COCINA", "CL-021"),
    (r"CON[CS]ERVADORA", "CL-022"),
    (r"GARRAFA", "CL-023"),
    (r"GAL[OÓ]N", "CL-024"),
    (r"TERMO", "CL-025"),
    (r"PLATO|TAZA|VASO|JARRA|VAJILLA|POSTRERA|BA[NÑ]ADOR|PLATILLO|CUBIERT|CUCHARA|TENEDOR|CUCHAR[OÓ]N", "CL-026"),
    (r"FLORERO", "CL-028"),
    (r"CANASTA", "CL-029"),
    (r"COJ[IÍ]N", "CL-030"),
    (r"MANTEL|CAMINO DE MESA|INDIVIDUAL|TELA|LONA|TAPIZ|AWAYO|SERVILLETERA|TULMA", "CL-031"),
]

MARCA_RULES = [
    (r"DOITE", "M-0001"),
    (r"QUECHUA|FORCLAZ", "M-0002"),
    (r"NORTH FACE|TNF", "M-0003"),
    (r"KING\s*CAMP", "M-0004"),
    (r"KAMA", "M-0005"),
    (r"GRUNHOF|GRUMHOF|GRUHOF|GRUMHOF", "M-0006"),
    (r"COLEMAN", "M-0007"),
    (r"MOMAMI|HARD\s*MO[MU]TAIN|HYPERL[AÁ]MINA", "M-0008"),
    (r"PELICAN", "M-0009"),
    (r"ALTUS", "M-0010"),
    (r"NATUREHIKE", "M-0011"),
    (r"TRULY", "M-0012"),
    (r"LATUMA", "M-0010"),
]

SKIP_DESC = re.compile(
    r"^(TOTAL|TOTALES|NO|DESCRIPCION|OFICINA|AREA|MATERIAL|FACTURA|RECIBO|INV\.|ALMACEN)\b",
    re.I,
)


def esc(value) -> str:
    if value is None:
        return "NULL"
    text = str(value).replace("\\", "\\\\").replace("'", "''")
    return f"'{text}'"


def clean(value) -> str | None:
    if value is None:
        return None
    text = re.sub(r"\s+", " ", str(value)).strip()
    return text or None


def match_code(text: str, rules) -> str | None:
    upper = text.upper()
    for pattern, code in rules:
        if re.search(pattern, upper, re.I):
            return code
    return None


def estado_from_row(nuevo, semi, usado, nota) -> str:
    flags = " ".join(str(x).upper() for x in (nuevo, semi, usado, nota) if x)
    if "BAJA" in flags:
        return "VIEJO"
    if nuevo and str(nuevo).upper() in {"SI", "SÍ", "1", "TRUE", "X"}:
        return "NUEVO"
    if semi and str(semi).upper() in {"SI", "SÍ", "1", "TRUE", "X"}:
        return "SEMINUEVO"
    if usado and str(usado).upper() in {"SI", "SÍ", "1", "TRUE", "X"}:
        return "USADO"
    if "NUEVO" in flags and "SEMI" not in flags:
        return "NUEVO"
    if "SEMI" in flags or "SEMINUEVO" in flags:
        return "SEMINUEVO"
    if "ANTIGUO" in flags or "VIEJO" in flags or "USADO" in flags:
        return "USADO"
    return "USADO"


def si_flag(value) -> int:
    return 1 if value and str(value).upper() in {"SI", "SÍ", "1", "TRUE", "X"} else 0


def parse_sheet_detail(ws, start_row: int, grupo: str):
    rows = []
    for r in range(start_row, ws.max_row + 1):
        desc = clean(ws.cell(r, 3).value)
        if not desc or SKIP_DESC.search(desc):
            continue
        # section titles: all caps short without quantity/code often
        color = clean(ws.cell(r, 4).value)
        qty = ws.cell(r, 5).value
        precio = ws.cell(r, 6).value
        codigo = clean(ws.cell(r, 7).value)
        nuevo = ws.cell(r, 8).value
        semi = ws.cell(r, 9).value
        usado = ws.cell(r, 10).value
        calif = ws.cell(r, 11).value
        arreglo = ws.cell(r, 12).value
        cambio = ws.cell(r, 13).value
        baja = ws.cell(r, 14).value
        nota = clean(ws.cell(r, 15).value) or clean(ws.cell(r, 16).value) or clean(ws.cell(r, 17).value)

        qty_num = None
        if isinstance(qty, (int, float)):
            qty_num = float(qty)
        precio_num = float(precio) if isinstance(precio, (int, float)) else None
        calif_num = int(calif) if isinstance(calif, (int, float)) and 1 <= float(calif) <= 10 else None

        # skip pure headers like "CARPA", "OLLAS"
        if qty_num is None and not codigo and (desc.isupper() or len(desc.split()) <= 3):
            # keep if looks like product with brand words
            if not match_code(desc, CLASE_RULES):
                continue
            if qty_num is None and not codigo:
                continue

        if qty_num is None:
            qty_num = 1.0 if codigo else None
        if qty_num is None or qty_num <= 0:
            continue

        rows.append(
            {
                "fila": r,
                "grupo": grupo,
                "descripcion": desc,
                "color": color,
                "cantidad": qty_num,
                "precio": precio_num,
                "codigo": codigo,
                "estado": estado_from_row(nuevo, semi, usado, nota),
                "calificacion": calif_num,
                "requiere_arreglo": si_flag(arreglo) or (1 if nota and "ARREGLO" in nota.upper() else 0),
                "requiere_cambio": si_flag(cambio),
                "requiere_baja": si_flag(baja) or (1 if nota and "BAJA" in nota.upper() else 0),
                "nota": nota,
            }
        )
    return rows


def parse_eventos(ws):
    rows = []
    for r in range(11, ws.max_row + 1):
        desc = clean(ws.cell(r, 3).value)
        if not desc or SKIP_DESC.search(desc):
            continue
        color = clean(ws.cell(r, 4).value)
        qty = ws.cell(r, 5).value
        precio = ws.cell(r, 6).value
        codigo = clean(ws.cell(r, 8).value)
        if not isinstance(qty, (int, float)) or qty <= 0:
            continue
        nuevo, semi, usado = ws.cell(r, 9).value, ws.cell(r, 10).value, ws.cell(r, 11).value
        calif = ws.cell(r, 12).value
        nota = clean(ws.cell(r, 16).value)
        rows.append(
            {
                "fila": r,
                "grupo": "EVENTOS",
                "descripcion": desc,
                "color": color,
                "cantidad": float(qty),
                "precio": float(precio) if isinstance(precio, (int, float)) else None,
                "codigo": codigo,
                "estado": estado_from_row(nuevo, semi, usado, nota),
                "calificacion": int(calif) if isinstance(calif, (int, float)) and 1 <= float(calif) <= 10 else None,
                "requiere_arreglo": si_flag(ws.cell(r, 13).value),
                "requiere_cambio": si_flag(ws.cell(r, 14).value),
                "requiere_baja": si_flag(ws.cell(r, 15).value),
                "nota": nota,
            }
        )
    return rows


def product_key(desc: str) -> str:
    return re.sub(r"\s+", " ", desc).strip().upper()


def main() -> int:
    if not XLSX.exists():
        print(f"No se encontró {XLSX}", file=sys.stderr)
        return 1

    wb = openpyxl.load_workbook(XLSX, data_only=True)
    items = []
    items += parse_sheet_detail(wb["INV MATERIAL ALPINISMO"], 54, "ALPINISMO")
    items += parse_sheet_detail(wb["INV.MATERIAL DE COCINA"], 40, "COCINA")
    items += parse_eventos(wb["INV. EVENTOS"])

    products: dict[str, dict] = {}
    units = []
    auto_n = 1

    for item in items:
        key = product_key(item["descripcion"])
        if key not in products:
            clase = match_code(item["descripcion"], CLASE_RULES) or (
                "CL-004" if item["grupo"] == "ALPINISMO" else "CL-026" if item["grupo"] == "COCINA" else "CL-031"
            )
            marca = match_code(item["descripcion"] + " " + (item["color"] or ""), MARCA_RULES)
            products[key] = {
                "cod_pro": f"PRO-{len(products)+1:04d}",
                "des_pro": item["descripcion"][:200],
                "cod_clase": clase,
                "cod_mar": marca,
                "color": (item["color"] or "")[:80] or None,
                "grupo": item["grupo"],
                "precio": item["precio"],
            }
        prod = products[key]
        if item["precio"] and not prod["precio"]:
            prod["precio"] = item["precio"]
        if item["color"] and not prod["color"]:
            prod["color"] = item["color"][:80]

        qty = int(item["cantidad"]) if item["cantidad"] == int(item["cantidad"]) else 1
        qty = max(qty, 1)
        for i in range(qty):
            code = item["codigo"]
            if qty > 1 and code:
                code = f"{code}-{i+1}" if i else code
            if not code:
                code = f"AUTO-{auto_n:05d}"
                auto_n += 1
            units.append(
                {
                    "cod_pro": prod["cod_pro"],
                    "codigo_unico": code[:50],
                    "color": item["color"],
                    "estado": item["estado"],
                    "calificacion": item["calificacion"],
                    "requiere_arreglo": item["requiere_arreglo"],
                    "requiere_cambio": item["requiere_cambio"],
                    "requiere_baja": item["requiere_baja"],
                    "nota": item["nota"],
                    "fila": item["fila"],
                    "grupo": item["grupo"],
                    "descripcion": item["descripcion"],
                    "precio": item["precio"],
                }
            )

    # dedupe codigo_unico
    seen = set()
    unique_units = []
    for u in units:
        base = u["codigo_unico"]
        code = base
        n = 2
        while code.upper() in seen:
            code = f"{base}-{n}"[:50]
            n += 1
        seen.add(code.upper())
        u["codigo_unico"] = code
        unique_units.append(u)

    lines = [
        "SET NAMES utf8mb4;",
        "SET FOREIGN_KEY_CHECKS=0;",
        "DELETE FROM documento_detalle;",
        "DELETE FROM documento_inventario;",
        "DELETE FROM inventario_unidad;",
        "DELETE FROM inventario_stock;",
        "DELETE FROM precio;",
        "DELETE FROM producto;",
        "SET FOREIGN_KEY_CHECKS=1;",
        "",
    ]

    for p in products.values():
        lines.append(
            "INSERT INTO producto (cod_pro, cod_clase, cod_mar, cod_um, des_pro, modelo, color, stock_minimo, estado) VALUES ("
            f"{esc(p['cod_pro'])}, {esc(p['cod_clase'])}, {esc(p['cod_mar'])}, 'UM-001', {esc(p['des_pro'])}, NULL, {esc(p['color'])}, 0, 'ACTIVO');"
        )
        if p["precio"]:
            lines.append(
                "INSERT INTO precio (cod_pro, cod_pre, fec_com, mon_pre, monto_pre) VALUES ("
                f"{esc(p['cod_pro'])}, 'PRE-EXCEL', CURDATE(), 'BOB', {float(p['precio']):.2f});"
            )

    today = date.today().isoformat()
    for u in unique_units:
        obs = u["nota"] or ""
        if u["color"]:
            obs = (f"Color: {u['color']}. " + obs).strip()
        lines.append(
            "INSERT INTO inventario_unidad (cod_pro, cod_almacen, codigo_unico, estado_fisico, calificacion, "
            "requiere_arreglo, requiere_cambio, requiere_baja, fecha_alta, fecha_baja, observaciones) VALUES ("
            f"{esc(u['cod_pro'])}, 'ALM-001', {esc(u['codigo_unico'])}, {esc(u['estado'])}, "
            f"{u['calificacion'] if u['calificacion'] is not None else 'NULL'}, "
            f"{u['requiere_arreglo']}, {u['requiere_cambio']}, {u['requiere_baja']}, "
            f"'{today}', {'CURDATE()' if u['requiere_baja'] else 'NULL'}, {esc(obs) if obs else 'NULL'});"
        )

    # stock aggregates
    stock = defaultdict(float)
    for u in unique_units:
        if not u["requiere_baja"]:
            stock[u["cod_pro"]] += 1
    for cod_pro, qty in stock.items():
        lines.append(
            "INSERT INTO inventario_stock (cod_pro, cod_almacen, cantidad, costo_promedio, ubicacion_detalle) VALUES ("
            f"{esc(cod_pro)}, 'ALM-001', {qty:.2f}, NULL, 'Importado desde Excel');"
        )

    # REF documents per sheet
    by_grupo = defaultdict(list)
    for u in unique_units:
        by_grupo[u["grupo"]].append(u)

    hoja_map = {
        "ALPINISMO": "INV MATERIAL ALPINISMO",
        "COCINA": "INV.MATERIAL DE COCINA",
        "EVENTOS": "INV. EVENTOS",
    }
    doc_n = 1
    for grupo, lista in by_grupo.items():
        hoja = hoja_map[grupo]
        meta = {
            "archivo": "Inventarios 2026 (1).xlsx",
            "hoja": hoja,
            "nota": "Referencia importada del Excel. No confirma disponibilidad operativa.",
            "anotaciones": [],
        }
        cod_doc = f"REF-{doc_n:03d}"
        doc_n += 1
        lines.append(
            "INSERT INTO documento_inventario (cod_doc, tipo, numero, fecha, proveedor, departamento, destino_a, grupo, observaciones, total) VALUES ("
            f"{esc(cod_doc)}, 'REF', {esc('XLS-'+grupo)}, CURDATE(), 'THAKI SRL', 'Almacenes', 'Importación Excel', "
            f"{esc(grupo)}, {esc(json.dumps(meta, ensure_ascii=False))}, NULL);"
        )
        for u in lista:
            item_obs = {
                "fila": u["fila"],
                "codigo": u["codigo_unico"],
                "color": u["color"],
                "nota": u["nota"],
                "producto": u["cod_pro"],
            }
            lines.append(
                "INSERT INTO documento_detalle (cod_doc, cod_pro, id_unidad, descripcion, cantidad, nota_observacion, estado_articulo, devuelto) VALUES ("
                f"{esc(cod_doc)}, {esc(u['cod_pro'])}, NULL, {esc(u['descripcion'][:200])}, 1, "
                f"{esc(json.dumps(item_obs, ensure_ascii=False))}, {u['calificacion'] if u['calificacion'] is not None else 'NULL'}, 0);"
            )

    OUT_SQL.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"Productos: {len(products)}")
    print(f"Unidades: {len(unique_units)}")
    print(f"SQL: {OUT_SQL}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
