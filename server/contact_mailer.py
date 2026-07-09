#!/usr/bin/env python3
import html
import os
import smtplib
from email.message import EmailMessage
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import parse_qs


HOST = "127.0.0.1"
PORT = int(os.environ.get("CONTACT_PORT", "8098"))
OWNER_EMAIL = os.environ.get("CONTACT_TO", "hichamaifout@gmail.com")
FROM_EMAIL = os.environ.get("CONTACT_FROM", "Faty Style <no-reply@fatystyle.fr>")
PUBLIC_BASE_URL = os.environ.get("PUBLIC_BASE_URL", "http://178.105.180.143:8082")


def clean(value, limit=1200):
    return html.escape((value or "").strip()[:limit], quote=False)


def send_email(to_email, subject, body, reply_to=None):
    message = EmailMessage()
    message["From"] = FROM_EMAIL
    message["To"] = to_email
    message["Subject"] = subject
    if reply_to:
        message["Reply-To"] = reply_to
    message.set_content(body)

    with smtplib.SMTP("127.0.0.1", 25, timeout=12) as smtp:
        smtp.send_message(message)


class ContactHandler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        print("%s - %s" % (self.address_string(), fmt % args), flush=True)

    def redirect(self, path):
        self.send_response(303)
        self.send_header("Location", path)
        self.end_headers()

    def do_POST(self):
        if self.path != "/api/contact":
            self.send_error(404)
            return

        length = int(self.headers.get("Content-Length", "0"))
        if length > 30000:
            self.redirect("/contact.html?erreur=taille")
            return

        payload = self.rfile.read(length).decode("utf-8", errors="replace")
        data = {key: values[0] for key, values in parse_qs(payload, keep_blank_values=True).items()}

        if data.get("website"):
            self.redirect("/message-envoye.html")
            return

        name = clean(data.get("name"), 160)
        phone = clean(data.get("phone"), 80)
        email = clean(data.get("email"), 220)
        request_type = clean(data.get("requestType"), 160)
        wanted_date = clean(data.get("date"), 80)
        message = clean(data.get("message"), 3000)

        if not name or not phone or not email or not message or "@" not in email:
            self.redirect("/contact.html?erreur=champs")
            return

        owner_body = "\n".join([
            "Nouvelle demande depuis le site Faty Style",
            "",
            f"Nom : {name}",
            f"Téléphone : {phone}",
            f"Email : {email}",
            f"Type de demande : {request_type}",
            f"Date souhaitée : {wanted_date}",
            "",
            "Message :",
            message,
            "",
            f"Page : {PUBLIC_BASE_URL}/contact.html"
        ])

        visitor_body = "\n".join([
            f"Bonjour {name},",
            "",
            "Nous avons bien reçu votre message envoyé à Faty Style.",
            "L'atelier vous répondra très bientôt.",
            "",
            "Pour une demande urgente, vous pouvez aussi contacter l'atelier par téléphone :",
            "07 68 65 56 43",
            "",
            "Rappel de votre demande :",
            f"Type de demande : {request_type}",
            f"Date souhaitée : {wanted_date}",
            "",
            "Votre message :",
            message,
            "",
            "Merci pour votre confiance,",
            "Faty Style"
        ])

        try:
            send_email(
                OWNER_EMAIL,
                f"Faty Style - Nouvelle demande : {request_type or 'Contact'}",
                owner_body,
                reply_to=email
            )
            send_email(
                email,
                "Faty Style - Nous avons bien reçu votre message",
                visitor_body,
                reply_to=OWNER_EMAIL
            )
        except Exception as exc:
            print("Mail error:", repr(exc), flush=True)
            self.redirect("/contact.html?erreur=envoi")
            return

        self.redirect("/message-envoye.html")


if __name__ == "__main__":
    HTTPServer((HOST, PORT), ContactHandler).serve_forever()
