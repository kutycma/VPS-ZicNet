#!/usr/bin/env python3
"""
H2Cloud Agency API Tester v6
Doc: https://api.h2cloud.vn/
"""

import requests
import json
import sys
import os

os.system('cls' if os.name == 'nt' else 'clear')

API_USERNAME = input("API Username: ").strip()
API_APP      = input("API App:      ").strip()
API_SECRET   = input("API Secret:   ").strip()

BASE_URL = "https://cloudserver.h2cloud.vn"

results = []

def pr(label, value, ok=True):
    results.append((ok, label, value))
    print(f"  {'✅' if ok else '❌'}  {label}: {value}")

def safe_json(r):
    try:
        if not r.text.strip():
            return None
        return r.json()
    except:
        return None

def fmt_num(val):
    try:
        return f"{int(val):,} VNĐ"
    except:
        return str(val)

def get_token():
    print("\n─── [1] Lấy auth-token ───")
    r = requests.post(
        f"{BASE_URL}/api/agency/get-token",
        params={"api-username": API_USERNAME, "api-app": API_APP, "api-secret": API_SECRET},
        headers={"Content-Type": "application/json"},
        timeout=10
    )
    print(f"  HTTP {r.status_code}")
    d = safe_json(r)
    if d and d.get("error") == 0:
        token = d["auth-token"]
        pr("Token", token[:30] + "...")
        return token
    pr("Lỗi", d.get("message", str(d)) if d else "response rỗng", ok=False)
    return None

def hdrs(token):
    return {
        "Content-Type": "application/json",
        "api-username": API_USERNAME,
        "api-app":      API_APP,
        "api-secret":   API_SECRET,
        "auth-token":   token,
    }

def test_detail(token):
    print("\n─── [2] Chi tiết đại lý ───")
    for ep in ["get-detail", "get-agency-detail", "get-info"]:
        r = requests.get(f"{BASE_URL}/api/agency/{ep}", headers=hdrs(token), timeout=8)
        d = safe_json(r)
        if not d:
            continue
        if d.get("error") == 0:
            data = d.get("data", {})
            pr("Tên đại lý", data.get("agency_name", "?"))
            pr("Số dư",      fmt_num(data.get("credit", "?")))
            pr("Tổng nạp",   fmt_num(data.get("total_credit", "?")))
            pr("Tổng tiêu",  fmt_num(data.get("total_expenses", "?")))
            pr("Tổng DV",    data.get("total_service", "?"))
            return
        pr(f"/{ep}", d.get("message", "?"), ok=False)
    pr("Không tìm thấy endpoint", "", ok=False)

def test_products(token):
    print("\n─── [3] Danh sách sản phẩm ───")
    r = requests.get(f"{BASE_URL}/api/agency/get-product", headers=hdrs(token), timeout=8)
    d = safe_json(r)
    if not d:
        pr("Lỗi", f"HTTP {r.status_code} — response rỗng", ok=False)
        return
    if d.get("error") == 0:
        products = d.get("products", {})
        vps_groups = products.get("vps", []) if isinstance(products, dict) else []
        total = sum(len(g.get("product", {})) for g in vps_groups)
        pr("Nhóm sản phẩm", len(vps_groups))
        pr("Tổng sản phẩm", total)
        for group in vps_groups[:3]:
            gname = group.get("group_product_name", "?")
            for pid, p in list(group.get("product", {}).items())[:2]:
                if not isinstance(p, dict):
                    continue
                pricing = p.get("pricing", {})
                price = "?"
                if isinstance(pricing, dict):
                    monthly = pricing.get("monthly", {})
                    if isinstance(monthly, dict):
                        price = monthly.get("price", "?")
                print(f"       [{gname}] {p.get('name','?')} | CPU:{p.get('cpu')} RAM:{p.get('ram')}G Disk:{p.get('disk')}G | {price} VNĐ/tháng")
    else:
        pr("Lỗi", d.get("message", str(d)), ok=False)

def test_os(token):
    print("\n─── [4] Danh sách OS ───")
    r = requests.get(f"{BASE_URL}/api/agency/get-list-os", headers=hdrs(token), timeout=8)
    d = safe_json(r)
    if not d:
        pr("Lỗi", f"HTTP {r.status_code} — response rỗng", ok=False)
        return
    if d.get("error") == 0:
        os_list = d.get("os-vps", [])
        pr("Tổng OS", len(os_list))
        for o in os_list[:8]:
            if isinstance(o, dict):
                oid   = o.get("os_id") or o.get("id") or o.get("os-id") or "?"
                oname = o.get("os_name") or o.get("name") or o.get("os-name") or str(list(o.values()))[:40]
            else:
                oid, oname = "?", str(o)[:40]
            print(f"       ID:{oid} | {oname}")
    else:
        pr("Lỗi", d.get("message", str(d)), ok=False)

def test_billing(token):
    print("\n─── [5] Billing cycle ───")
    r = requests.get(f"{BASE_URL}/api/agency/get-list-billing-cycle", headers=hdrs(token), timeout=8)
    d = safe_json(r)
    if not d:
        pr("Lỗi", f"HTTP {r.status_code} — response rỗng", ok=False)
        return
    if d.get("error") == 0:
        cycles = d.get("billing-cycle", [])
        pr("Tổng chu kỳ", len(cycles))
        for c in cycles:
            print(f"       {c.get('billing-key','?')} → {c.get('billing-name','?')}")
    else:
        pr("Lỗi", d.get("message", str(d)), ok=False)

def test_recharge_info(token):
    print("\n─── [6] Thông tin nạp tiền ───")
    r = requests.get(f"{BASE_URL}/api/agency/get-info-recharge", headers=hdrs(token), timeout=8)
    d = safe_json(r)
    if not d:
        pr("Lỗi", f"HTTP {r.status_code} — response rỗng", ok=False)
        return
    if d.get("error") == 0:
        limit = d.get("limit", {})
        pr("Nạp tối thiểu", fmt_num(limit.get("min", "?")))
        pr("Hạn mức tối đa", fmt_num(limit.get("max", "?")))
        bankings = d.get("bankings", {})
        pr("Số ngân hàng", len(bankings) if isinstance(bankings, (dict, list)) else "?")
        if isinstance(bankings, dict):
            for key, bank in list(bankings.items())[:3]:
                if isinstance(bank, dict):
                    bname  = bank.get('bank-name') or bank.get('bank_name', '?')
                    bstk   = bank.get('account-number') or bank.get('account_number', '?')
                    bowner = bank.get('account-name') or bank.get('account_name', '?')
                    print(f"       {bname} | STK: {bstk} | {bowner}")
    else:
        pr("Lỗi", d.get("message", str(d)), ok=False)

def test_list_vps(token):
    print("\n─── [7] Danh sách VPS VN ───")
    for ep in ["list-vps-vn", "get-list-vps-vn", "list-service-vn", "list-service"]:
        r = requests.post(
            f"{BASE_URL}/api/agency/{ep}",
            headers=hdrs(token),
            json={"type": "all", "qtt": 5, "page": 0},
            timeout=8
        )
        d = safe_json(r)
        if not d:
            continue
        if d.get("error") == 0:
            items = d.get("list-service", d.get("data", {}))
            if isinstance(items, dict):
                items = list(items.values())
            pr("Tổng VPS", len(items))
            for v in items[:5]:
                print(f"       IP:{v.get('ip','?')} | {v.get('vps-status','?')} | Hết hạn:{v.get('next_due_date','?')}")
            return
    pr("Không có VPS hoặc endpoint chưa xác định", "", ok=False)

def test_state(token):
    print("\n─── [8] Danh sách bang (VPS NN) ───")
    for ep in ["get-state", "get-list-state"]:
        r = requests.get(f"{BASE_URL}/api/agency/{ep}", headers=hdrs(token), timeout=8)
        d = safe_json(r)
        if not d:
            continue
        if d.get("error") == 0:
            states = d.get("state", [])
            pr("Tổng bang", len(states))
            items = list(states)[:5] if isinstance(states, list) else list(states.keys())[:5]
            for s in items:
                print(f"       {s}")
            return

# ─── MAIN ───────────────────────────────────────────────────
print("╔══════════════════════════════════════╗")
print("║     H2Cloud Agency API Tester v6     ║")
print("╚══════════════════════════════════════╝")
print(f"  Base URL : {BASE_URL}")
print(f"  Username : {API_USERNAME}")
print(f"  App      : {API_APP[:8]}...")
try:
    my_ip = requests.get("https://api.ipify.org", timeout=5).text.strip()
except:
    my_ip = "Không xác định"
print(f"  IP test  : {my_ip}")

token = get_token()
if not token:
    print("\n❌ Không lấy được token.")
    sys.exit(1)

test_detail(token)
test_products(token)
test_os(token)
test_billing(token)
test_recharge_info(token)
test_list_vps(token)
test_state(token)

# ─── SUMMARY ────────────────────────────────────────────────
passed = [r for r in results if r[0]]
failed = [r for r in results if not r[0]]

print("\n" + "═"*42)
print(f"  📊 KẾT QUẢ: {len(passed)} thành công / {len(failed)} thất bại")
print("═"*42)

if passed:
    print("\n✅ THÀNH CÔNG:")
    for _, label, value in passed:
        print(f"   • {label}: {value}")

if failed:
    print("\n❌ THẤT BẠI:")
    for _, label, value in failed:
        print(f"   • {label}: {value}")

print()
