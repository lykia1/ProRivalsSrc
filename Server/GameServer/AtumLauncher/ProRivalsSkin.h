#pragma once
// Launcher-only GDI skin. No network, patching or login behaviour is changed.
namespace ProRivalsSkin
{
    inline void Text(CDC& dc, LPCWSTR value, CRect rect, int size,
                     COLORREF color, int weight = FW_NORMAL, UINT align = DT_LEFT)
    {
        CFont font;
        font.Attach(::CreateFontW(-size, 0, 0, 0, weight, FALSE, FALSE, FALSE,
            DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
            CLEARTYPE_QUALITY, DEFAULT_PITCH, L"Segoe UI"));
        CFont* old = dc.SelectObject(&font);
        int mode = dc.SetBkMode(TRANSPARENT);
        COLORREF previous = dc.SetTextColor(color);
        ::DrawTextW(dc.GetSafeHdc(), value, -1, &rect,
            align | DT_SINGLELINE | DT_VCENTER | DT_END_ELLIPSIS | DT_NOPREFIX);
        dc.SetTextColor(previous);
        dc.SetBkMode(mode);
        dc.SelectObject(old);
    }
    inline void Panel(CDC& dc, CRect r)
    {
        dc.FillSolidRect(r, RGB(16, 26, 39));
        dc.Draw3dRect(r, RGB(43, 65, 83), RGB(43, 65, 83));
    }
    inline void Background(CDC& dc)
    {
        dc.FillSolidRect(0, 0, 800, 536, RGB(9, 16, 26));
        dc.FillSolidRect(0, 0, 800, 22, RGB(16, 26, 39));
        dc.FillSolidRect(0, 22, 800, 2, RGB(31, 203, 199));
        Text(dc, L"PRORIVALS", CRect(18, 0, 190, 22), 12, RGB(187, 206, 220), FW_SEMIBOLD);
        Text(dc, L"PRO", CRect(565, 32, 620, 68), 28, RGB(233, 243, 250), FW_BOLD);
        Text(dc, L"RIVALS", CRect(622, 32, 782, 68), 28, RGB(31, 203, 199), FW_BOLD);
        Text(dc, L"FLIGHT COMBAT ONLINE", CRect(567, 70, 782, 86), 10, RGB(126, 153, 175));
        Text(dc, L"HABERLER / DUYURULAR", CRect(239, 34, 538, 58), 10, RGB(126, 153, 175));
        Panel(dc, CRect(16, 64, 541, 491));
        Panel(dc, CRect(562, 97, 786, 298));
        Text(dc, L"SUNUCU SE\u00c7\u0130M\u0130", CRect(574, 101, 773, 125), 12, RGB(187, 206, 220), FW_SEMIBOLD);
        Panel(dc, CRect(562, 305, 786, 393));
        Text(dc, L"Kullan\u0131c\u0131 ad\u0131", CRect(574, 323, 650, 344), 11, RGB(187, 206, 220));
        Text(dc, L"\u015eifre", CRect(574, 346, 650, 367), 11, RGB(187, 206, 220));
        dc.Draw3dRect(CRect(651, 324, 773, 343), RGB(43, 65, 83), RGB(43, 65, 83));
        dc.Draw3dRect(CRect(651, 347, 773, 366), RGB(43, 65, 83), RGB(43, 65, 83));
        Text(dc, L"Beni hat\u0131rla", CRect(705, 377, 784, 397), 10, RGB(187, 206, 220));
        Text(dc, L"Pencere", CRect(715, 451, 787, 470), 10, RGB(187, 206, 220));
        Panel(dc, CRect(568, 471, 780, 491));
        dc.FillSolidRect(16, 496, 768, 1, RGB(43, 65, 83));
        dc.Draw3dRect(CRect(15, 515, 785, 527), RGB(43, 65, 83), RGB(43, 65, 83));
        dc.Draw3dRect(CRect(0, 0, 800, 536), RGB(43, 65, 83), RGB(43, 65, 83));
    }
    inline void Button(LPDRAWITEMSTRUCT item, LPCWSTR label, bool primary, bool hover)
    {
        CDC dc;
        dc.Attach(item->hDC);
        CRect r(item->rcItem);
        bool disabled = (item->itemState & ODS_DISABLED) != 0;
        bool down = (item->itemState & ODS_SELECTED) != 0;
        COLORREF fill = primary ? RGB(22, 145, 151) : RGB(22, 36, 51);
        if (hover) fill = primary ? RGB(28, 177, 180) : RGB(35, 55, 73);
        if (down) fill = RGB(17, 91, 108);
        if (disabled) fill = RGB(29, 40, 51);
        dc.FillSolidRect(r, fill);
        dc.Draw3dRect(r, disabled ? RGB(43, 65, 83) : RGB(31, 203, 199), RGB(43, 65, 83));
        Text(dc, label, r, primary ? 22 : 12,
            disabled ? RGB(113, 134, 151) : RGB(235, 247, 251), FW_SEMIBOLD, DT_CENTER);
        if (item->itemState & ODS_FOCUS)
        {
            r.DeflateRect(4, 4);
            dc.DrawFocusRect(r);
        }
        dc.Detach();
    }
}
class CProRivalsLaunchButton : public CKbcButton
{
public:
    virtual void DrawItem(LPDRAWITEMSTRUCT item)
    {
        if (GetDlgCtrlID() == IDGO)
        {
            DRAWITEMSTRUCT state = *item;
            if (m_bDisable) state.itemState |= ODS_DISABLED;
            ProRivalsSkin::Button(&state, L"OYUNA G\u0130R", true, m_bHover != FALSE);
        }
        else CKbcButton::DrawItem(item);
    }
};
class CProRivalsChromeButton : public CBitmapButton
{
public:
    virtual void DrawItem(LPDRAWITEMSTRUCT item)
    {
        LPCWSTR label = GetDlgCtrlID() == IDCAN ? L"\u00d7" :
            GetDlgCtrlID() == IDMIN ? L"\u2013" : L"WEB S\u0130TES\u0130";
        ProRivalsSkin::Button(item, label, false, false);
    }
};
