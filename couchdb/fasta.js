function(doc) {
  var path = [];

  if (doc.phylum_reg) {
    if (doc.phylum_reg) {
      path.push(doc.phylum_reg);
    }
    if (doc.class_reg) {
      path.push(doc.class_reg);
    }
    if (doc.order_reg) {
      path.push(doc.order_reg);
    }
    if (doc.species_reg) {
      path.push(doc.species_reg);
    }
    
    // FASTA format sequence
    var fasta = ">" + doc._id;
    if (doc.species_reg) {
        fasta += "|" + doc.species_reg;
    }
    if (doc.accession) {
        fasta += "|" + doc.accession;
    }
    fasta += "\n";

    var chunks = doc.nucraw.match(/.{1,60}/g);
    fasta += chunks.join("\n");
    fasta += "\n";
    
    emit(path, fasta);
  }

}