import Phaser from 'phaser';

export default class Maquis extends Phaser.Scene {
    tablesData = [
        { sx: 95,  sy: 250, x: 92,  y: 262, occupe: false, active: true,  sprite: null },
        { sx: 175, sy: 235, x: 172, y: 247, occupe: false, active: true,  sprite: null },
        { sx: 130, sy: 320, x: 127, y: 332, occupe: false, active: false, sprite: null },
        { sx: 215, sy: 305, x: 212, y: 317, occupe: false, active: false, sprite: null },
        { sx: 110, sy: 410, x: 107, y: 422, occupe: false, active: false, sprite: null },
        { sx: 195, sy: 400, x: 192, y: 412, occupe: false, active: false, sprite: null },
        { sx: 260, sy: 360, x: 257, y: 372, occupe: false, active: false, sprite: null },
        { sx: 160, sy: 480, x: 157, y: 492, occupe: false, active: false, sprite: null },
    ];
    ENTREE  = { x: 300, y: 660 };
    SORTIE  = { x: 300, y: 820 };
    CUISINE = { x: 300, y: 380 };
    clients = [];
    pret = false;
    solde = 0;
    coutTable = 2000;
    serveuses = [];
    spritesServeuse = ['serveuse', 'serveuse2', 'serveuse3', 'serveuse4'];
    coutsServeuse   = [0, 3000, 12000, 40000];
    posServeuses    = [
        { x: 250, y: 300 },
        { x: 325, y: 285 },
        { x: 200, y: 270 },
        { x: 390, y: 270 },
    ];
    maxServeuses = 4;
    _enPause = false;
    _promoActive = false;
    _promoCooldown = false;
    delaiSpawnNormal = 1500;
    delaiSpawnPromo  = 600;
    niveauMenu = 1;
    paiementsParNiveau  = [0, 500, 1000, 1500, 2000];
    coutsUpgradeMenu    = [0, 8000, 25000, 60000];
    spritesChef         = ['boucantier', 'chef2', 'chef3', 'chef4'];

    constructor() {
        super('maquis');
    }
    preload() {
        this.load.image('maquis-bg', '/jeu-assets/img/maquis-bg.png');
        this.load.image('serveuse', '/jeu-assets/img/serveuse.png');
        this.load.image('boucantier', '/jeu-assets/img/boucantier.png');
        this.load.image('client1', '/jeu-assets/img/client1.png');
        this.load.image('client2', '/jeu-assets/img/client2.png');
        this.load.image('client3', '/jeu-assets/img/client3.png');
        this.load.image('table', '/jeu-assets/img/table.png');
        this.load.image('serveuse2', '/jeu-assets/img/serveuse2.png');
        this.load.image('serveuse3', '/jeu-assets/img/serveuse3.png');
        this.load.image('serveuse4', '/jeu-assets/img/serveuse4.png');
        this.load.image('chef2', '/jeu-assets/img/chef2.png');
        this.load.image('chef3', '/jeu-assets/img/chef3.png');
        this.load.image('chef4', '/jeu-assets/img/chef4.png');
    }
    create() {
        const cam = this.cameras.main;
        this.add.image(cam.width / 2, cam.height / 2, 'maquis-bg')
            .setOrigin(0.5)
            .setDisplaySize(cam.width, cam.height);
        this.add.text(16, 16, 'Le Maquis', { fontSize: '20px', color: '#f4e3b4' });
        this.soldeText = this.add.text(404, 16, '0 cauris', {
            fontSize: '20px', color: '#f4e3b4', fontStyle: 'bold'
        }).setOrigin(1, 0).setDepth(10000);

        this.musiqueActive = true;

        this.tablesData.forEach(t => { if (t.active) this.poserTable(t); });

        const addPerso = (key, x, y, h = 150) => {
            const s = this.add.image(x, y, key).setOrigin(0.5, 1);
            s.setScale(h / s.height);
            return s;
        };

        this.boucantier = addPerso('boucantier', 360, 365, 155);
        this.boucantier.setDepth(this.boucantier.y);
        this.serveuses.push(this.ajouterServeuse(this.posServeuses[0].x, this.posServeuses[0].y, this.spritesServeuse[0]));

        this.demarrerSpawn(this.delaiSpawnNormal);

        this.input.on('pointerdown', (p, objetsCliques) => {
            if (objetsCliques.length > 0) return;
            this.spawnClient();
        });

        this.btnTable = this.add.container(110, 605).setDepth(10000);
        const bg = this.add.rectangle(0, 0, 120, 46, 0xb0862e).setStrokeStyle(2, 0xffffff);
        const label = this.add.text(0, 0, '+ Table\n2 000 cauris', {
            fontSize: '13px', color: '#1a211d', fontStyle: 'bold', align: 'center'
        }).setOrigin(0.5);
        this.btnTable.add([bg, label]);
        this.btnTableBg    = bg;
        this.btnTableLabel = label;
        bg.setInteractive({ useHandCursor: true });
        bg.on('pointerdown', () => this.acheterTable(label));

        this.btnServeuse = this.add.container(310, 605).setDepth(10000);
        const bgS = this.add.rectangle(0, 0, 120, 46, 0x2e6eb0).setStrokeStyle(2, 0xffffff);
        const labelS = this.add.text(0, 0, '+ Serveuse\n3 000 cauris', {
            fontSize: '13px', color: '#ffffff', fontStyle: 'bold', align: 'center'
        }).setOrigin(0.5);
        this.btnServeuse.add([bgS, labelS]);
        this.btnServeuseBg    = bgS;
        this.btnServeuseLabel = labelS;
        bgS.setInteractive({ useHandCursor: true });
        bgS.on('pointerdown', () => this.acheterServeuse());

        this.btnMenu = this.add.container(210, 548).setDepth(10000);
        const bgM = this.add.rectangle(0, 0, 130, 46, 0x2e8b57).setStrokeStyle(2, 0xffffff);
        const labelM = this.add.text(0, 0, 'Améliorer menu\n8 000 cauris', {
            fontSize: '12px', color: '#ffffff', fontStyle: 'bold', align: 'center'
        }).setOrigin(0.5);
        this.btnMenu.add([bgM, labelM]);
        this.btnMenuBg    = bgM;
        this.btnMenuLabel = labelM;
        bgM.setInteractive({ useHandCursor: true });
        bgM.on('pointerdown', () => this.ameliorerMenu());

        this.pret = true;
        this.chargerSave().then(save => { if (save) this.appliquerSave(save); });

        // Fonctions globales pour piloter le jeu depuis le HTML
        window.jeuPause       = () => { if (!this._enPause) this.togglePause(); };
        window.jeuReprendre   = () => { if (this._enPause) this.reprendre(); };
        window.jeuTogglePause = () => this.togglePause();
        window.jeuMusique     = () => this.toggleMusique();
        window.jeuEtatMusique = () => this.musiqueActive;
        window.jeuPromo       = () => this.lancerPromo();
        window.jeuPromoDispo  = () => (!this._promoActive && !this._promoCooldown);
        window.jeuQuitter     = () => {
            this.sauvegarder();
            if (!this._enPause) this.togglePause();
        };
        window.jeuRelancer    = () => {
            this._enPause = false;
            this.scene.restart();
        };
    }

    demarrerSpawn(delai) {
        if (this._spawnTimer) this._spawnTimer.remove();
        this._spawnTimer = this.time.addEvent({
            delay: delai, loop: true, callback: () => this.spawnClient(),
        });
    }

    lancerPromo() {
        if (this._promoActive || this._promoCooldown) return;
        this._promoActive = true;
        this.demarrerSpawn(this.delaiSpawnPromo);
        this.afficherMessage('📢 Promo ! Les clients affluent !');
        this.btnPromoBg?.setFillStyle(0x999088);
        this.time.delayedCall(8000, () => {
            this._promoActive = false;
            this.demarrerSpawn(this.delaiSpawnNormal);
            this._promoCooldown = true;
            this.btnPromoBg?.setFillStyle(0x999088);
            this.time.delayedCall(20000, () => {
                this._promoCooldown = false;
                this.btnPromoBg?.setFillStyle(0xff7043);
                window._htmlPromoUpdate?.();
            });
        });
    }

    spawnClient() {
        if (this._enPause) return;
        const table = this.tablesData.find(t => t.active && !t.occupe);
        if (!table) return;
        table.occupe = true;

        const key = Phaser.Utils.Array.GetRandom(['client1', 'client2', 'client3']);
        const c = this.add.image(this.ENTREE.x, this.ENTREE.y, key).setOrigin(0.5, 1);
        c.setScale(120 / c.height);
        c.setDepth(this.ENTREE.y);

        this.tweens.add({
            targets: c,
            x: table.x,
            y: table.y,
            duration: 1500,
            ease: 'Sine.InOut',
            onUpdate: () => c.setDepth(c.y),
            onComplete: () => { c.assis = true; c.servi = false; },
        });

        this.clients.push({ obj: c, table });
    }

    acheterTable(label) {
        if (this.solde < this.coutTable || !this.tablesData.some(t => !t.active)) {
            this.tweens.add({ targets: this.btnTable, x: '+=4', duration: 50, yoyo: true, repeat: 3 });
            if (!this.tablesData.some(t => !t.active)) {
                this.afficherMessage('Plus de place dans le maquis !\nAgrandis-le pour ajouter des tables.');
            }
            return;
        }
        const tableVerrou = this.tablesData.find(t => !t.active);
        this.solde -= this.coutTable;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
        tableVerrou.active = true;
        this.poserTable(tableVerrou);
        tableVerrou.sprite.setScale(0);
        this.tweens.add({
            targets: tableVerrou.sprite,
            scale: 60 / tableVerrou.sprite.height,
            duration: 300, ease: 'Back.Out',
        });
        this.coutTable = Math.round(this.coutTable * 1.5);
        label.setText('+ Table\n' + this.coutTable.toLocaleString('fr-FR') + ' cauris');
        this.btnTableBg.setFillStyle(0x37b24d);
        this.time.delayedCall(150, () => this.majBoutons());
        this.sauvegarder();
    }

    majBoutons() {
        const okTable = this.solde >= this.coutTable && this.tablesData.some(t => !t.active);
        this.btnTableBg.setFillStyle(okTable ? 0xb0862e : 0x555049);
        this.btnTableLabel.setAlpha(okTable ? 1 : 0.5);

        const n = this.serveuses.length;
        if (n >= this.maxServeuses) {
            this.btnServeuseLabel.setText('Équipe complète');
            this.btnServeuseBg.setFillStyle(0x555049);
            this.btnServeuseLabel.setAlpha(1);
        } else {
            const cout = this.coutsServeuse[n];
            this.btnServeuseLabel.setText('+ Serveuse\n' + cout.toLocaleString('fr-FR') + ' cauris');
            const ok = this.solde >= cout;
            this.btnServeuseBg.setFillStyle(ok ? 0x2e6eb0 : 0x555049);
            this.btnServeuseLabel.setAlpha(ok ? 1 : 0.5);
        }
    }

    async chargerSave() {
        try {
            const r = await fetch('/api/jeu/load', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) return null;
            return await r.json();
        } catch (e) { return null; }
    }

    sauvegarder() {
        clearTimeout(this._saveTimer);
        this._saveTimer = setTimeout(() => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/api/jeu/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    solde:          Math.floor(this.solde),
                    tables_actives: this.tablesData.filter(t => t.active).length,
                    nb_serveuses:   this.serveuses.length,
                    niveau_menu:    this.niveauMenu,
                    cout_table:     this.coutTable,
                    cout_serveuse:  this.coutsServeuse[this.serveuses.length] ?? 0,
                }),
            }).then(r => { if (!r.ok) console.warn('Sauvegarde échouée', r.status); })
              .catch(e => console.warn('Sauvegarde erreur', e));
        }, 800);
    }

    ajouterServeuse(x, y, key = 'serveuse') {
        const s = this.add.image(x, y, key).setOrigin(0.5, 1);
        s.setScale(150 / s.height);
        s.setDepth(y);
        s.home = { x, y };
        s.busy = false;
        return s;
    }

    acheterServeuse() {
        const n = this.serveuses.length;
        if (n >= this.maxServeuses) {
            this.tweens.add({ targets: this.btnServeuse, x: '+=4', duration: 50, yoyo: true, repeat: 3 });
            this.afficherMessage('Plus de place pour embaucher !\nAgrandis ton maquis.');
            return;
        }
        const cout = this.coutsServeuse[n];
        if (this.solde < cout) {
            this.tweens.add({ targets: this.btnServeuse, x: '+=4', duration: 50, yoyo: true, repeat: 3 });
            return;
        }
        this.solde -= cout;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
        const pos = this.posServeuses[n];
        const s = this.ajouterServeuse(pos.x, pos.y, this.spritesServeuse[n]);
        s.setScale(0);
        this.tweens.add({ targets: s, scale: 150 / s.height, duration: 300, ease: 'Back.Out' });
        this.serveuses.push(s);
        this.majBoutons();
        this.sauvegarder();
    }

    poserTable(t) {
        const s = this.add.image(t.sx, t.sy, 'table').setOrigin(0.5, 0.7);
        s.setScale(60 / s.height);
        s.setDepth(t.sy);
        t.sprite = s;
        return s;
    }

    servirProchain() {
        this.serveuses.forEach(s => {
            if (s.busy) return;
            const cible = this.clients.find(c => c.obj.assis && !c.obj.servi && !c.obj.enCours);
            if (!cible) return;

            s.busy = true;
            cible.obj.enCours = true;

            this.tweens.add({
                targets: s, x: cible.table.x + 28, y: cible.table.y + 10,
                duration: 900, ease: 'Sine.InOut',
                onUpdate: () => s.setDepth(s.y),
                onComplete: () => {
                    this.time.delayedCall(500, () => {
                        cible.obj.servi = true;
                        cible.obj.setTint(0x9bffa8);

                        // le client mange, paie, repart
                        this.time.delayedCall(2500, () => {
                            const paiement = this.paiementsParNiveau[this.niveauMenu];
                            this.solde += paiement;
                            this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
                            this.sauvegarder();
                            const pop = this.add.text(cible.obj.x, cible.obj.y - 120, '+' + paiement + ' cauris', {
                                fontSize: '18px', color: '#ffe08a', fontStyle: 'bold'
                            }).setOrigin(0.5).setDepth(10000);
                            this.tweens.add({ targets: pop, y: pop.y - 30, alpha: 0, duration: 900,
                                onComplete: () => pop.destroy() });
                            cible.obj.clearTint();
                            this.tweens.add({
                                targets: cible.obj, x: this.SORTIE.x, y: this.SORTIE.y,
                                duration: 1400, ease: 'Sine.InOut',
                                onUpdate: () => cible.obj.setDepth(cible.obj.y),
                                onComplete: () => {
                                    cible.table.occupe = false;
                                    cible.obj.destroy();
                                    this.clients = this.clients.filter(x => x !== cible);
                                },
                            });
                        });

                        // la serveuse retourne à SA base
                        this.tweens.add({
                            targets: s, x: s.home.x, y: s.home.y,
                            duration: 900, ease: 'Sine.InOut',
                            onUpdate: () => s.setDepth(s.y),
                            onComplete: () => { s.busy = false; },
                        });
                    });
                },
            });
        });
    }

    appliquerSave(save) {
        this.solde     = save.solde;
        this.coutTable = save.cout_table;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
        this.btnTableLabel.setText('+ Table\n' + this.coutTable.toLocaleString('fr-FR') + ' cauris');
        this.tablesData.forEach((t, i) => {
            if (i < save.tables_actives && !t.active) {
                t.active = true;
                this.poserTable(t);
            }
        });
        for (let i = 1; i < (save.nb_serveuses || 1); i++) {
            this.serveuses.push(this.ajouterServeuse(this.posServeuses[i].x, this.posServeuses[i].y, this.spritesServeuse[i]));
        }
        if (save.niveau_menu && save.niveau_menu > 1) {
            this.niveauMenu = save.niveau_menu;
            const x = this.boucantier.x, y = this.boucantier.y,
                  scale = this.boucantier.scale, depth = this.boucantier.depth;
            this.boucantier.destroy();
            this.boucantier = this.add.image(x, y, this.spritesChef[this.niveauMenu - 1])
                .setOrigin(0.5, 1).setScale(scale).setDepth(depth);
        }
        this.majBoutonMenu();
    }

    ameliorerMenu() {
        if (this.niveauMenu >= 4) {
            this.afficherMessage('Menu au maximum !\nTon chef est une légende du maquis.');
            return;
        }
        const cout = this.coutsUpgradeMenu[this.niveauMenu];
        if (this.solde < cout) {
            this.tweens.add({ targets: this.btnMenu, x: '+=4', duration: 50, yoyo: true, repeat: 3 });
            return;
        }
        this.solde -= cout;
        this.niveauMenu++;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');

        const x = this.boucantier.x, y = this.boucantier.y,
              scale = this.boucantier.scale, depth = this.boucantier.depth;
        this.boucantier.destroy();
        this.boucantier = this.add.image(x, y, this.spritesChef[this.niveauMenu - 1])
            .setOrigin(0.5, 1).setDepth(depth);
        this.boucantier.setScale(0);
        this.tweens.add({ targets: this.boucantier, scale, duration: 300, ease: 'Back.Out' });

        this.majBoutonMenu();
        this.sauvegarder();
    }

    majBoutonMenu() {
        if (this.niveauMenu >= 4) {
            this.btnMenuLabel.setText('Menu max');
            this.btnMenuBg.setFillStyle(0x555049);
            this.btnMenuLabel.setAlpha(1);
            return;
        }
        const cout = this.coutsUpgradeMenu[this.niveauMenu];
        this.btnMenuLabel.setText('Améliorer menu\n' + cout.toLocaleString('fr-FR') + ' cauris');
        const ok = this.solde >= cout;
        this.btnMenuBg.setFillStyle(ok ? 0x2e8b57 : 0x555049);
        this.btnMenuLabel.setAlpha(ok ? 1 : 0.5);
    }

    toggleMusique() {
        this.musiqueActive = !this.musiqueActive;
        this.icoMusique?.setText('♪');
        this.icoMusique?.setAlpha(this.musiqueActive ? 1 : 0.4);
        // TODO polish : if (this.musique) this.musiqueActive ? this.musique.resume() : this.musique.pause();
    }

    togglePause() {
        if (this._enPause) { this.reprendre(); return; }
        this._enPause = true;
        this.time.paused = true;
        this.tweens.pauseAll();
        this._overlayPause = this.add.container(0, 0).setDepth(50000);
        const voile = this.add.rectangle(210, 320, 420, 640, 0x000000, 0.6)
            .setInteractive({ useHandCursor: true });
        const txt = this.add.text(210, 290, 'PAUSE', { fontSize: '42px', color: '#ffffff',
            fontStyle: 'bold' }).setOrigin(0.5);
        const sous = this.add.text(210, 340, 'Touchez pour reprendre', { fontSize: '16px',
            color: '#ffce54' }).setOrigin(0.5);
        this._overlayPause.add([voile, txt, sous]);
        voile.on('pointerdown', () => this.reprendre());
        this.pret = false;
    }

    reprendre() {
        this._enPause = false;
        this.time.paused = false;
        this.tweens.resumeAll();
        if (this._overlayPause) { this._overlayPause.destroy(); this._overlayPause = null; }
        this.pret = true;
    }

    afficherMessage(texte) {
        if (this._msgActif) this._msgActif.destroy();
        const m = this.add.container(210, 90).setDepth(99999);
        const bg = this.add.rectangle(0, 0, 360, 50, 0x000000, 0.78).setStrokeStyle(2, 0xffce54);
        const t = this.add.text(0, 0, texte, { fontSize: '14px', color: '#ffce54',
            fontStyle: 'bold', align: 'center', wordWrap: { width: 340 } }).setOrigin(0.5);
        m.add([bg, t]); m.setAlpha(0); this._msgActif = m;
        this.tweens.add({ targets: m, alpha: 1, duration: 200, yoyo: true, hold: 1800,
            onComplete: () => { m.destroy(); if (this._msgActif === m) this._msgActif = null; } });
    }

    update() {
        if (!this.pret) return;
        this.servirProchain();
        this.majBoutons();
        this.majBoutonMenu();
    }
}
