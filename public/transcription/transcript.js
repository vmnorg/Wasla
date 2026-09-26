export class Transcript {
  constructor() {
    this.items = new Map();
  }
  apply(event) {
    if (!event.item_id) return;
    const item = this.items.get(event.item_id) || { text: "", final: false };
    if (event.type === "committed") item.previous = event.previous_item_id;
    if (event.type === "delta" && !item.final) item.text += event.text;
    if (event.type === "partial" && !item.final) item.text = event.text;
    if (event.type === "final") { item.text = event.text; item.final = true; }
    this.items.set(event.item_id, item);
  }
  text() {
    const ordered = [], seen = new Set();
    const visit = (id) => {
      if (seen.has(id) || !this.items.has(id)) return;
      seen.add(id);
      const item = this.items.get(id);
      visit(item.previous);
      ordered.push(item.text);
    };
    for (const id of this.items.keys()) visit(id);
    return ordered.filter(Boolean).join(" ");
  }
}
