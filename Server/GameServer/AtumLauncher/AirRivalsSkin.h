#pragma once
#include <atlimage.h>
#pragma comment(lib, "ole32.lib")
// Launcher-only GDI skin. No network, patching or login behaviour is changed.
namespace AirRivalsSkin
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
    // Decode the original website PNG/JPEG from executable resources.
    // CImage preserves the logo's alpha; no colour key or opaque backdrop.
    inline bool Image(CDC& dc, UINT id, CRect target, const CRect* crop = NULL)
    {
        HINSTANCE instance = AfxGetResourceHandle();
        HRSRC resource = ::FindResource(instance, MAKEINTRESOURCE(id), RT_RCDATA);
        if (!resource) return false;
        DWORD size = ::SizeofResource(instance, resource);
        HGLOBAL loaded = ::LoadResource(instance, resource);
        const void* bytes = loaded ? ::LockResource(loaded) : NULL;
        if (!bytes || !size) return false;
        HGLOBAL memory = ::GlobalAlloc(GMEM_MOVEABLE, size);
        if (!memory) return false;
        void* buffer = ::GlobalLock(memory);
        if (!buffer) { ::GlobalFree(memory); return false; }
        memcpy(buffer, bytes, size);
        ::GlobalUnlock(memory);
        IStream* stream = NULL;
        if (FAILED(::CreateStreamOnHGlobal(memory, TRUE, &stream)))
        { ::GlobalFree(memory); return false; }
        ATL::CImage image;
        HRESULT result = image.Load(stream);
        stream->Release();
        if (FAILED(result) || image.IsNull()) return false;
        CRect source = crop ? *crop : CRect(0, 0, image.GetWidth(), image.GetHeight());
        return image.Draw(dc.GetSafeHdc(), target.left, target.top,
            target.Width(), target.Height(), source.left, source.top,
            source.Width(), source.Height()) != FALSE;
    }
    inline void Gradient(CDC& dc, CRect r, COLORREF top, COLORREF bottom)
    {
        const int height = r.Height();
        if (height <= 0) return;
        for (int y = 0; y < height; ++y)
        {
            int divisor = height > 1 ? height - 1 : 1;
            COLORREF color = RGB(
                (GetRValue(top)*(divisor-y) + GetRValue(bottom)*y)/divisor,
                (GetGValue(top)*(divisor-y) + GetGValue(bottom)*y)/divisor,
                (GetBValue(top)*(divisor-y) + GetBValue(bottom)*y)/divisor);
            dc.FillSolidRect(r.left, r.top+y, r.Width(), 1, color);
        }
    }
    inline void Frame(CDC& dc, CRect r)
    {
        dc.Draw3dRect(r, RGB(112, 159, 188), RGB(3, 10, 19));
        r.DeflateRect(1, 1);
        dc.Draw3dRect(r, RGB(36, 70, 96), RGB(29, 53, 75));
    }
    inline void Background(CDC& dc)
    {
        dc.FillSolidRect(0, 0, 800, 536, RGB(9, 16, 26));
        CBitmap scene;
        if (scene.LoadBitmap(IDB_AIRRIVALS_SCENE))
        {
            CDC source;
            source.CreateCompatibleDC(&dc);
            CBitmap* old = source.SelectObject(&scene);
            int previous = dc.SetStretchBltMode(HALFTONE);
            CPoint origin;
            ::SetBrushOrgEx(dc.GetSafeHdc(), 0, 0, &origin);
            dc.BitBlt(0, 0, 800, 536, &source, 0, 0, SRCCOPY);
            // Refit illustrated steel frames to the existing native controls.
            dc.StretchBlt(0, 22, 800, 74, &source, 0, 0, 800, 108, SRCCOPY);
            dc.StretchBlt(11, 61, 535, 434, &source, 16, 107, 512, 377, SRCCOPY);
            dc.StretchBlt(556, 94, 236, 207, &source, 531, 119, 255, 139, SRCCOPY);
            dc.StretchBlt(556, 301, 236, 95, &source, 531, 261, 255, 110, SRCCOPY);
            dc.StretchBlt(556, 454, 236, 40, &source, 531, 421, 255, 62, SRCCOPY);
            ::SetBrushOrgEx(dc.GetSafeHdc(), origin.x, origin.y, NULL);
            dc.SetStretchBltMode(previous);
            source.SelectObject(old);
        }
        Gradient(dc, CRect(0, 0, 800, 22), RGB(36, 65, 86), RGB(6, 18, 29));
        Text(dc, L"AIRRIVALS", CRect(18, 0, 190, 22), 12, RGB(204, 227, 241), FW_SEMIBOLD);
        // Crop only transparent padding when drawing; original PNG is untouched.
        CRect logoSource(190, 81, 601, 219);
        if (!Image(dc, IDR_AIRRIVALS_LOGO, CRect(559, 23, 789, 93), &logoSource))
            Text(dc, L"AIRRIVALS", CRect(562, 27, 786, 83), 30, RGB(224, 240, 255), FW_BOLD, DT_CENTER);
        Gradient(dc, CRect(235, 35, 530, 59), RGB(30, 57, 79), RGB(7, 20, 34));
        Frame(dc, CRect(235, 35, 530, 59));
        Text(dc, L"HABERLER / DUYURULAR", CRect(245, 35, 525, 59), 11, RGB(226, 179, 76), FW_SEMIBOLD);
        Gradient(dc, CRect(570, 103, 778, 126), RGB(28, 52, 73), RGB(6, 20, 34));
        Text(dc, L"SUNUCU SE\u00c7\u0130M\u0130", CRect(578, 103, 773, 126), 12, RGB(226, 179, 76), FW_SEMIBOLD);
        Text(dc, L"Kullan\u0131c\u0131 ad\u0131", CRect(574, 323, 650, 344), 11, RGB(202, 219, 233));
        Text(dc, L"\u015eifre", CRect(574, 346, 650, 367), 11, RGB(202, 219, 233));
        Frame(dc, CRect(651, 324, 773, 343));
        Frame(dc, CRect(651, 347, 773, 366));
        Text(dc, L"Beni hat\u0131rla", CRect(705, 377, 784, 397), 10, RGB(202, 219, 233));
        Text(dc, L"Pencere", CRect(715, 451, 787, 470), 10, RGB(202, 219, 233));
        Frame(dc, CRect(568, 471, 780, 491));
        // This local welcome artwork remains underneath configured web news.
        Gradient(dc, CRect(19, 67, 538, 488), RGB(16, 38, 58), RGB(4, 15, 28));
        Text(dc, L"AirRivals'a ho\u015f geldin!", CRect(34, 78, 520, 105), 19, RGB(229, 183, 77), FW_BOLD);
        Image(dc, IDR_AIRRIVALS_NEWS, CRect(33, 117, 524, 389));
        Frame(dc, CRect(31, 115, 526, 391));
        Text(dc, L"G\u00d6KY\u00dcZ\u00dc SEN\u0130N", CRect(34, 402, 520, 432), 22, RGB(211, 232, 246), FW_BOLD);
        Text(dc, L"Ulusunu se\u00e7. Filona kat\u0131l. Sava\u015fa haz\u0131rlan.", CRect(34, 435, 520, 458), 13, RGB(167, 193, 213));
        Text(dc, L"Sunucunu se\u00e7erek oyuna giri\u015f yapabilirsin.", CRect(34, 461, 520, 481), 11, RGB(123, 155, 179));
        Gradient(dc, CRect(16, 496, 785, 515), RGB(15, 31, 47), RGB(5, 16, 27));
        Frame(dc, CRect(15, 515, 785, 527));
        Frame(dc, CRect(0, 0, 800, 536));
    }
    inline void Button(LPDRAWITEMSTRUCT item, LPCWSTR label, bool primary, bool hover)
    {
        CDC dc;
        dc.Attach(item->hDC);
        CRect r(item->rcItem);
        bool disabled = (item->itemState & ODS_DISABLED) != 0;
        bool down = (item->itemState & ODS_SELECTED) != 0;
        COLORREF top = primary ? RGB(169, 121, 33) : RGB(38, 72, 99);
        COLORREF bottom = primary ? RGB(57, 31, 6) : RGB(5, 20, 35);
        if (hover) { top = primary ? RGB(218, 167, 63) : RGB(54, 99, 131); }
        if (down) { top = RGB(36, 35, 27); bottom = RGB(87, 64, 23); }
        if (disabled) { top = RGB(48, 57, 64); bottom = RGB(15, 26, 36); }
        Gradient(dc, r, top, bottom);
        Frame(dc, r);
        CRect inner(r); inner.DeflateRect(3, 3);
        dc.Draw3dRect(inner, primary && !disabled ? RGB(220, 183, 103) : RGB(65, 102, 132), RGB(11, 18, 24));
        CRect highlight(r); highlight.DeflateRect(5, 5);
        dc.FillSolidRect(highlight.left, highlight.top, highlight.Width(), 1,
            primary && !disabled ? RGB(227, 192, 119) : RGB(82, 118, 145));
        Text(dc, label, r, primary ? 22 : 12,
            disabled ? RGB(119, 139, 154) : RGB(248, 229, 182), FW_SEMIBOLD, DT_CENTER);
        if (item->itemState & ODS_FOCUS)
        {
            r.DeflateRect(4, 4);
            dc.DrawFocusRect(r);
        }
        dc.Detach();
    }
}
class CAirRivalsLaunchButton : public CKbcButton
{
public:
    virtual void DrawItem(LPDRAWITEMSTRUCT item)
    {
        if (GetDlgCtrlID() == IDGO)
        {
            DRAWITEMSTRUCT state = *item;
            if (m_bDisable) state.itemState |= ODS_DISABLED;
            AirRivalsSkin::Button(&state, L"OYUNA G\u0130R", true, m_bHover != FALSE);
        }
        else CKbcButton::DrawItem(item);
    }
};
class CAirRivalsChromeButton : public CBitmapButton
{
public:
    virtual void DrawItem(LPDRAWITEMSTRUCT item)
    {
        LPCWSTR label = GetDlgCtrlID() == IDCAN ? L"\u00d7" :
            GetDlgCtrlID() == IDMIN ? L"\u2013" : L"WEB S\u0130TES\u0130";
        AirRivalsSkin::Button(item, label, false, false);
    }
};
