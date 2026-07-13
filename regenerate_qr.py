import os, re
from pypdf import PdfReader, PdfWriter
from pypdf.generic import DecodedStreamObject
from reportlab.graphics.barcode.qr import QrCodeWidget

CERTS_DIR = 'C:/xampp/htdocs/CertsVerify/certs_clickable/'

def get_qr_matrix(data_str):
    qr = QrCodeWidget(data_str, barBorder=0)
    # Force encode
    from reportlab.graphics.barcode import qr as qrmod
    code = qrmod.PyQrCodeWidget(data_str, barBorder=0)
    return code

def generate_qr_pdf_ops(data_str, qr_x, qr_y, qr_size):
    from reportlab.graphics.barcode.qr import QrCodeWidget
    import io
    from reportlab.pdfgen import canvas
    from reportlab.lib.units import pt
    
    # Use reportlab to render QR to a temp PDF, then extract the ops
    buf = io.BytesIO()
    c = canvas.Canvas(buf, pagesize=(qr_size + 20, qr_size + 20))
    
    qr = QrCodeWidget(data_str, barBorder=0)
    bounds = qr.getBounds()
    w = bounds[2] - bounds[0]
    h = bounds[3] - bounds[1]
    
    from reportlab.graphics.shapes import Drawing
    from reportlab.graphics import renderPDF
    d = Drawing(qr_size, qr_size)
    qr.transform = [qr_size/w, 0, 0, qr_size/h, 0, 0]
    d.add(qr)
    renderPDF.draw(d, c, 0, 0)
    c.save()
    
    # Read that PDF's content stream  
    buf.seek(0)
    r2 = PdfReader(buf)
    pg = r2.pages[0]
    ops = pg['/Contents'].get_object().get_data().decode('latin-1')
    
    # Offset all coordinates
    def offset_ops(ops, dx, dy):
        result = []
        for line in ops.split('\n'):
            # Shift rectangle ops: "n x y w h re"
            m = re.match(r'(n\s+)([\d.]+)\s+([\d.]+)(\s+[\d.]+\s+[\d.]+\s+re\s+f\*)', line)
            if m:
                x = float(m.group(2)) + dx
                y = float(m.group(3)) + dy
                result.append(f'{m.group(1)}{x:.4f} {y:.4f}{m.group(4)}')
            else:
                result.append(line)
        return '\n'.join(result)
    
    return offset_ops(ops, qr_x, qr_y)

def process_pdf(filepath):
    reader = PdfReader(filepath)
    page = reader.pages[0]
    text = page.extract_text()
    
    annot_obj = page['/Annots'][0].get_object()
    uri = annot_obj['/A']['/URI']
    cert_id = re.search(r'id=(CV-[\w-]+)', uri).group(1)
    
    lines = [l.strip() for l in text.split('\n') if l.strip()]
    student_name, course, grade = '', '', ''
    
    for i, line in enumerate(lines):
        if line == 'This is to certify that' and i+1 < len(lines):
            student_name = lines[i+1]
        if line == 'CLASSIFICATION' and i+1 < len(lines):
            grade = lines[i+1]
    
    course_match = re.search(r'degree of\n(.+?)(?:\n)', text)
    if course_match:
        course = course_match.group(1).strip()
    
    # Plain text QR data
    qr_data = f"{cert_id}\\{student_name}\\{course}\\{grade}"
    
    content_obj = page['/Contents'].get_object()
    stream_data = content_obj.get_data().decode('latin-1')
    
    # Split at QR section
    qr_marker_re = re.search(
        r'\.419608 \.447059 \.501961 rg\nBT /F1 6\.5 Tf 7\.8 TL ET\nBT 1 0 0 1 \S+ 129\.285 Tm \(Scan to verify\)',
        stream_data
    )
    if not qr_marker_re:
        print(f'  SKIP: no QR marker in {os.path.basename(filepath)}')
        return False
    
    cert_body = stream_data[:qr_marker_re.start()]
    
    # Build new QR section
    qr_x, qr_y, qr_size = 478.1656, 52.535, 78.75
    
    label_ops = (
        '.419608 .447059 .501961 rg\n'
        'BT /F1 6.5 Tf 7.8 TL ET\n'
        'BT 1 0 0 1 497.8521 129.285 Tm (Scan to verify) Tj T* ET\n'
    )
    qr_ops = generate_qr_pdf_ops(qr_data, qr_x, qr_y, qr_size)
    footer_ops = (
        '.117647 .227451 .372549 rg\n'
        'n 42.525 42.525 510.2256 28.35 re f*\n'
        '1 1 1 rg\n'
        'BT /F1 6 Tf 7.2 TL ET\n'
        f'BT 1 0 0 1 100 53.865 Tm '
        f'(ID: {cert_id}  |  {student_name}  |  {course}  |  {grade}) Tj T* ET\n \n\n'
    )
    
    new_stream = cert_body + label_ops + '\n' + qr_ops + '\n' + footer_ops
    
    writer = PdfWriter()
    writer.add_page(page)
    new_content = DecodedStreamObject()
    new_content.set_data(new_stream.encode('latin-1'))
    page['/Contents'] = writer._add_object(new_content)
    
    with open(filepath, 'wb') as f:
        writer.write(f)
    
    print(f'  OK: {cert_id} | {student_name} | {course} | {grade}')
    return True

for fname in sorted(os.listdir(CERTS_DIR)):
    if fname.endswith('.pdf'):
        print(f'Processing: {fname}')
        try:
            process_pdf(os.path.join(CERTS_DIR, fname))
        except Exception as e:
            print(f'  ERROR: {e}')