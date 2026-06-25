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
        { x: 250, y: 265 },
        { x: 325, y: 285 },
        { x: 155, y: 210 },
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
    niveauMaquis = 1;
    coutsAgrandir       = [0, 50000, 150000];
    maxTablesParPalier  = [6, 8];   // palier 1 : 6 tables max, palier 2 : 8 tables max

    // Données par palier : table sprite, chef de départ, serveuses, clients
    palierData = [
        {  // palier 1
            tableSprite:    'table',
            tableHeight:    50,
            boucantierKey:  'boucantier',
            boucantierPos:  { x: 360, y: 365 },
            spritesChef:    ['boucantier', 'chef2', 'chef3', 'chef4'],
            spritesServeuse: ['serveuse', 'serveuse2', 'serveuse3', 'serveuse4'],
            posServeuses:   [
                { x: 250, y: 265 },
                { x: 325, y: 285 },
                { x: 155, y: 210 },
                { x: 390, y: 270 },
            ],
            spritesClients:  ['client1', 'client2', 'client3'],
        },
        {  // palier 2 — tout plus cher, clients paient plus
            tableSprite:    'table2',
            tableHeight:    85,
            coutTableDepart:    5000,
            coutsServeuse:      [0, 12000, 40000, 100000],
            coutsUpgradeMenu:   [0, 20000, 60000, 150000],
            paiementsParNiveau: [0, 1500, 3000, 5000, 8000],
            boucantierKey:  'boucantier2',
            boucantierPos:  { x: 355, y: 360 },
            spritesChef:    ['boucantier2', 'chef6', 'chef7', 'chef8'],
            spritesServeuse: ['serveuse5', 'serveuse6', 'serveuse7', 'serveuse8'],
            posServeuses:   [
                { x: 215, y: 338 },  // serveuse5 — gauche kiosque
                { x: 295, y: 325 },  // serveuse6 — centre kiosque
                { x: 150, y: 318 },  // serveuse7 — loin gauche
                { x: 100, y: 322 },  // serveuse8 — à gauche de serveuse7
            ],
            spritesClients:  ['client4', 'client5', 'client6'],
            // tables remontées au-dessus des boutons (boutons à y≈490-605)
            tablesCoords: [
                { sx: 75,  sy: 395, x: 72,  y: 407 },  // rangée 1 — gauche
                { sx: 185, sy: 378, x: 182, y: 390 },  // rangée 1 — centre
                { sx: 288, sy: 398, x: 285, y: 410 },  // rangée 1 — droite
                { sx: 90,  sy: 458, x: 87,  y: 470 },  // rangée 2 — gauche
                { sx: 205, sy: 442, x: 202, y: 454 },  // rangée 2 — centre
                { sx: 300, sy: 462, x: 297, y: 474 },  // rangée 2 — droite
                { sx: 112, sy: 513, x: 109, y: 525 },  // rangée 3 — gauche
                { sx: 228, sy: 500, x: 225, y: 512 },  // rangée 3 — centre
            ],
        },
    ];

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
        this.load.image('maquis-bg-2',  '/jeu-assets/img/maquis-bg-2.png');
        // this.load.image('maquis-bg-3',  '/jeu-assets/img/maquis-bg-3.png');  // à décommenter quand disponible
        this.load.image('table2',       '/jeu-assets/img/table2.png');
        this.load.image('boucantier2',  '/jeu-assets/img/boucantier2.png');
        this.load.image('chef5',        '/jeu-assets/img/chef5.png');
        this.load.image('chef6',        '/jeu-assets/img/chef6.png');
        this.load.image('chef7',        '/jeu-assets/img/chef7.png');
        this.load.image('chef8',        '/jeu-assets/img/chef8.png');
        this.load.image('serveuse5',    '/jeu-assets/img/serveuse5.png');
        this.load.image('serveuse6',    '/jeu-assets/img/serveuse6.png');
        this.load.image('serveuse7',    '/jeu-assets/img/serveuse7.png');
        this.load.image('serveuse8',    '/jeu-assets/img/serveuse8.png');
        this.load.image('client4',      '/jeu-assets/img/client4.png');
        this.load.image('client5',      '/jeu-assets/img/client5.png');
        this.load.image('client6',      '/jeu-assets/img/client6.png');
        this.load.audio('musique', '/jeu-assets/audio/musique.mp3');
        this.load.audio('cash',    '/jeu-assets/audio/cash.mp3');
        this.load.audio('client',  '/jeu-assets/audio/client.mp3');
        this.load.audio('achat',   '/jeu-assets/audio/achat.mp3');
        this.load.audio('promo',   '/jeu-assets/audio/promo.mp3');
    }
    create() {
        // --- reset état mutable (scene.restart réutilise la même instance) ---
        this.tablesData.forEach(t => {
            if (t.sprite) { t.sprite.destroy(); t.sprite = null; }
            t.occupe = false;
        });
        this.tablesData.forEach((t, i) => { t.active = (i < 2); });
        if (this.serveuses?.length) {
            this.serveuses.forEach(s => { if (s?.destroy) s.destroy(); });
        }
        this.serveuses = [];
        if (this.clients?.length) {
            this.clients.forEach(c => { if (c.obj?.destroy) c.obj.destroy(); });
        }
        this.clients      = [];
        this.solde        = 0;
        this.coutTable    = 2000;
        this.coutsServeuse = [0, 3000, 12000, 40000];
        this.niveauMenu   = 1;
        this._enPause     = false;
        this._promoActive  = false;
        this._promoCooldown = false;
        // nettoie les sons de la session précédente (scene.restart crée de nouveaux objets)
        this.musique?.stop(); this.musique?.destroy(); this.musique = null;
        if (this.sons) { Object.values(this.sons).forEach(s => s?.destroy()); this.sons = null; }
        this.fondMaquis?.destroy(); this.fondMaquis = null;
        this.niveauMaquis = 1;
        this._tweenBtnAgrandir?.stop(); this._tweenBtnAgrandir = null;
        this.btnAgrandir?.destroy(); this.btnAgrandir = null;
        // --- fin reset ---

        const cam = this.cameras.main;
        this.fondMaquis = this.add.image(cam.width / 2, cam.height / 2, 'maquis-bg')
            .setOrigin(0.5)
            .setDisplaySize(cam.width, cam.height)
            .setDepth(0);
        this.add.text(16, 16, 'Le Maquis', { fontSize: '20px', color: '#f4e3b4' });
        this.soldeText = this.add.text(404, 16, '0 cauris', {
            fontSize: '20px', color: '#f4e3b4', fontStyle: 'bold'
        }).setOrigin(1, 0).setDepth(10000);

        this.musiqueActive = true;
        this.audioMuted = false;

        this.sons = {
            cash:   this.sound.add('cash',   { volume: 0.5 }),
            client: this.sound.add('client', { volume: 0.5 }),
            achat:  this.sound.add('achat',  { volume: 0.5 }),
            promo:  this.sound.add('promo',  { volume: 0.6 }),
        };
        this.musique = this.sound.add('musique', { volume: 0.3, loop: true });
        this.jouerSon = (cle) => {
            if (this.audioMuted) return;
            try { this.sons[cle]?.play(); } catch(e){}
        };
        if (!this.audioMuted) { this.musique.play(); }
        // démarrage différé si navigateur bloque l'autoplay
        this.input.once('pointerdown', () => {
            if (!this.audioMuted && !this.musique.isPlaying) { this.musique.play(); }
        });

        this.tablesData.forEach(t => { if (t.active) this.poserTable(t); });

        const addPerso = (key, x, y, h = 150) => {
            const s = this.add.image(x, y, key).setOrigin(0.5, 1);
            s.setScale(h / s.height);
            return s;
        };

        const pData = this.palierData[this.niveauMaquis - 1] ?? this.palierData[0];
        this.spritesServeuse = pData.spritesServeuse;
        this.spritesClients  = pData.spritesClients;
        const bPos = pData.boucantierPos ?? { x: 360, y: 365 };
        this.boucantier = addPerso(pData.boucantierKey, bPos.x, bPos.y, 155);
        this.boucantier.setDepth(this.boucantier.y);
        const pos0 = (pData.posServeuses ?? this.posServeuses)[0];
        this.serveuses.push(this.ajouterServeuse(pos0.x, pos0.y, this.spritesServeuse[0]));

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

        this.btnAgrandir = this.add.container(210, 490).setDepth(10000);
        const bgA = this.add.rectangle(0, 0, 170, 40, 0x7b3f00).setStrokeStyle(2, 0xffe08a);
        this.btnAgrandirLabel = this.add.text(0, 0, '🎉 Agrandir\n50 000 cauris', {
            fontSize: '12px', color: '#ffe08a', fontStyle: 'bold', align: 'center'
        }).setOrigin(0.5);
        this.btnAgrandir.add([bgA, this.btnAgrandirLabel]);
        this.btnAgrandirBg = bgA;
        bgA.setInteractive({ useHandCursor: true });
        bgA.on('pointerdown', () => this.agrandirMaquis());

        this.pret = true;
        this.chargerSave().then(save => { if (save) this.appliquerSave(save); });

        // Fonctions globales pour piloter le jeu depuis le HTML
        window.jeuPause       = () => { if (!this._enPause) this.togglePause(); };
        window.jeuReprendre   = () => { if (this._enPause) this.reprendre(); };
        window.jeuTogglePause = () => this.togglePause();
        window.jeuMusique     = () => this.toggleMusique();
        window.jeuEtatMusique = () => !this.audioMuted;
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
        this.jouerSon('promo');
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

        const key = Phaser.Utils.Array.GetRandom(this.spritesClients ?? ['client1', 'client2', 'client3']);
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
        const maxTables   = this.maxTablesParPalier[this.niveauMaquis - 1] ?? 8;
        const nbActives   = this.tablesData.filter(t => t.active).length;
        const plafondAtteint = nbActives >= maxTables;
        const plusDeTables   = !this.tablesData.some(t => !t.active);

        if (this.solde < this.coutTable || plafondAtteint || plusDeTables) {
            this.tweens.add({ targets: this.btnTable, x: '+=4', duration: 50, yoyo: true, repeat: 3 });
            if (plafondAtteint) {
                this.afficherMessage('Palier max atteint !\nAgrandis ton maquis pour débloquer plus de tables. 🎉');
            } else if (plusDeTables) {
                this.afficherMessage('Plus de place dans le maquis !\nAgrandis-le pour ajouter des tables.');
            }
            return;
        }
        this.rebond(this.btnTable);
        this.jouerSon('achat');
        const tableVerrou = this.tablesData.find(t => !t.active);
        this.solde -= this.coutTable;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
        tableVerrou.active = true;
        this.poserTable(tableVerrou);
        tableVerrou.sprite.setScale(0);
        this.tweens.add({
            targets: tableVerrou.sprite,
            scale: (this.palierData[this.niveauMaquis - 1]?.tableHeight ?? 50) / tableVerrou.sprite.height,
            duration: 300, ease: 'Back.Out',
        });
        this.coutTable = Math.round(this.coutTable * 1.5);
        label.setText('+ Table\n' + this.coutTable.toLocaleString('fr-FR') + ' cauris');
        this.btnTableBg.setFillStyle(0x37b24d);
        this.time.delayedCall(150, () => this.majBoutons());
        this.sauvegarder();
    }

    majBoutons() {
        const maxTables = this.maxTablesParPalier[this.niveauMaquis - 1] ?? 8;
        const nbActives = this.tablesData.filter(t => t.active).length;
        const okTable   = this.solde >= this.coutTable
                       && this.tablesData.some(t => !t.active)
                       && nbActives < maxTables;
        this.btnTableBg.setFillStyle(okTable ? 0xb0862e : 0x555049);
        this.btnTableLabel.setAlpha(okTable ? 1 : 0.5);

        const n = this.serveuses.length;
        if (n >= this.maxServeuses) {
            this.btnServeuseLabel.setText('Équipe complète');
            this.btnServeuseBg.setFillStyle(0x555049);
            this.btnServeuseLabel.setAlpha(1);
        } else {
            const cout = this.coutsServeuse[n] ?? 0;
            if (cout === 0) { this.btnServeuseLabel.setText('Équipe complète'); this.btnServeuseBg.setFillStyle(0x555049); return; }
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
                    niveau_maquis:  this.niveauMaquis,
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
        this.rebond(this.btnServeuse);
        this.jouerSon('achat');
        this.solde -= cout;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
        const posArr = (this.palierData[this.niveauMaquis - 1]?.posServeuses) ?? this.posServeuses;
        const pos = posArr[n];
        const s = this.ajouterServeuse(pos.x, pos.y, this.spritesServeuse[n]);
        s.setScale(0);
        this.tweens.add({ targets: s, scale: 150 / s.height, duration: 300, ease: 'Back.Out' });
        this.serveuses.push(s);
        this.majBoutons();
        this.sauvegarder();
    }

    poserTable(t) {
        const pData    = this.palierData[this.niveauMaquis - 1] ?? this.palierData[0];
        const tableKey = pData.tableSprite ?? 'table';
        const tableH   = pData.tableHeight ?? 50;
        const s = this.add.image(t.sx, t.sy, tableKey).setOrigin(0.5, 0.7);
        s.setScale(tableH / s.height);
        s.setDepth(t.sy);
        t.sprite = s;
        return s;
    }

    servirProchain() {
        // Serveuses libres dans un ordre aléatoire
        const libres = Phaser.Utils.Array.Shuffle([...this.serveuses].filter(s => !s.busy));
        libres.forEach(s => {
            // Clients disponibles — on prend le plus proche (plus petit delta distance)
            const dispo = this.clients.filter(c => c.obj.assis && !c.obj.servi && !c.obj.enCours);
            if (!dispo.length) return;
            // Choisit le client dont la table est la plus proche de la serveuse
            const cible = dispo.reduce((best, c) => {
                const d  = Math.hypot(c.table.x - s.x, c.table.y - s.y);
                const db = Math.hypot(best.table.x - s.x, best.table.y - s.y);
                return d < db ? c : best;
            });
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
                        this.jouerSon('client');

                        // le client mange, paie, repart
                        this.time.delayedCall(2500, () => {
                            const paiement = this.paiementsParNiveau[this.niveauMenu];
                            this.solde += paiement;
                            this.majSoldeAnime(this.solde);
                            this.animerPieces(cible.obj.x, cible.obj.y);
                            this.jouerSon('cash');
                            this.tweens.add({
                                targets: cible.obj,
                                scaleX: cible.obj.scaleX * 1.1, scaleY: cible.obj.scaleY * 1.1,
                                duration: 150, yoyo: true,
                            });
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
            const posArr2 = (this.palierData[this.niveauMaquis - 1]?.posServeuses) ?? this.posServeuses;
            this.serveuses.push(this.ajouterServeuse(posArr2[i].x, posArr2[i].y, this.spritesServeuse[i]));
        }
        if (save.niveau_menu && save.niveau_menu > 1) {
            this.niveauMenu = save.niveau_menu;
            const x = this.boucantier.x, y = this.boucantier.y,
                  scale = this.boucantier.scale, depth = this.boucantier.depth;
            this.boucantier.destroy();
            this.boucantier = this.add.image(x, y, (this.palierData[this.niveauMaquis - 1]?.spritesChef ?? this.spritesChef)[this.niveauMenu - 1])
                .setOrigin(0.5, 1).setScale(scale).setDepth(depth);
        }
        this.majBoutonMenu();
        this.niveauMaquis = save.niveau_maquis || 1;
        this.majDecorMaquis(false);
        this.appliquerVisuelsPalier(false);
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
        this.rebond(this.btnMenu);
        this.jouerSon('achat');
        this.solde -= cout;
        this.niveauMenu++;
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');

        const x = this.boucantier.x, y = this.boucantier.y,
              scale = this.boucantier.scale, depth = this.boucantier.depth;
        this.boucantier.destroy();
        this.boucantier = this.add.image(x, y, (this.palierData[this.niveauMaquis - 1]?.spritesChef ?? this.spritesChef)[this.niveauMenu - 1])
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
        this.audioMuted = !this.audioMuted;
        this.musiqueActive = !this.audioMuted;
        if (this.audioMuted) { this.musique?.stop(); }
        else { this.musique?.play(); }
        this.icoMusique?.setAlpha(this.audioMuted ? 0.4 : 1);
        if (window._htmlMusiqueUpdate) window._htmlMusiqueUpdate();
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

    // Synchronise TOUS les visuels avec le palier courant (tables, boucantier, sprites)
    appliquerVisuelsPalier(celebrer = false) {
        const pData = this.palierData[this.niveauMaquis - 1] ?? this.palierData[0];

        // Met à jour les pools de sprites et les coûts du palier
        this.spritesServeuse = pData.spritesServeuse;
        this.spritesClients  = pData.spritesClients;
        if (pData.coutsServeuse)      this.coutsServeuse      = pData.coutsServeuse;
        if (pData.coutsUpgradeMenu)   this.coutsUpgradeMenu   = pData.coutsUpgradeMenu;
        if (pData.paiementsParNiveau) this.paiementsParNiveau = pData.paiementsParNiveau;

        // Retexture toutes les tables déjà posées
        const tableKey    = pData.tableSprite;
        const tableH      = pData.tableHeight ?? 50;
        const tablesCoord = pData.tablesCoords;
        this.tablesData.forEach((t, i) => {
            // Toujours mettre à jour les coords (actives ou non) pour que acheterTable() soit correct
            if (tablesCoord && tablesCoord[i]) {
                const tc = tablesCoord[i];
                t.sx = tc.sx; t.sy = tc.sy; t.x = tc.x; t.y = tc.y;
            }
            if (!t.active || !t.sprite) return;
            if (tablesCoord && tablesCoord[i]) {
                t.sprite.x = t.sx; t.sprite.y = t.sy;
                t.sprite.setDepth(t.sy);
            }
            if (this.textures.exists(tableKey)) {
                t.sprite.setTexture(tableKey);
                t.sprite.setScale(tableH / t.sprite.height);
            }
        });

        // Retexture les serveuses déjà créées
        const posArr3 = pData.posServeuses ?? this.posServeuses;
        this.serveuses.forEach((s, i) => {
            const key = this.spritesServeuse[i];
            const pos = posArr3[i];
            if (s && key && this.textures.exists(key)) {
                s.setTexture(key).setScale(150 / s.height);
            }
            if (s && pos) {
                s.x = pos.x; s.y = pos.y;
                s.home = { x: pos.x, y: pos.y };
                s.setDepth(pos.y);
            }
        });

        // Remplace le boucantier si besoin
        const chefKey = pData.boucantierKey;
        if (this.boucantier && this.boucantier.texture.key !== chefKey && this.textures.exists(chefKey)) {
            const x = this.boucantier.x, y = this.boucantier.y,
                  scale = this.boucantier.scale, depth = this.boucantier.depth;
            this.boucantier.destroy();
            this.boucantier = this.add.image(x, y, chefKey).setOrigin(0.5, 1).setDepth(depth);
            if (celebrer) {
                this.boucantier.setScale(0);
                this.tweens.add({ targets: this.boucantier, scale, duration: 300, ease: 'Back.Out' });
            } else {
                this.boucantier.setScale(scale);
            }
        }
    }

    // Applique la texture du fond selon this.niveauMaquis (pas de calcul auto)
    majDecorMaquis(celebrer = false) {
        const cles = ['maquis-bg', 'maquis-bg-2', 'maquis-bg-3'];
        const cle  = cles[this.niveauMaquis - 1] ?? 'maquis-bg';
        const cible = this.textures.exists(cle) ? cle : 'maquis-bg';
        const cam  = this.cameras.main;
        this.fondMaquis.setTexture(cible).setDisplaySize(cam.width, cam.height);
        if (celebrer) {
            this.cameras.main.flash(300, 255, 255, 255, false);
            this.tweens.add({
                targets: this.fondMaquis,
                scale: 1.05, duration: 200, yoyo: true, ease: 'Sine.InOut',
            });
            this.afficherMessage('Ton maquis s\'agrandit ! 🎉');
        }
    }

    _conditionsAgrandir() {
        const maxTables = this.maxTablesParPalier[this.niveauMaquis - 1] ?? 8;
        const tablesOk   = this.tablesData.filter(t => t.active).length >= maxTables;
        const servOk     = this.serveuses.length >= this.maxServeuses;
        const chefOk     = this.niveauMenu >= 4;
        const soldOk     = this.solde >= (this.coutsAgrandir[this.niveauMaquis] ?? Infinity);
        return { tablesOk, servOk, chefOk, soldOk, tout: tablesOk && servOk && chefOk && soldOk };
    }

    agrandirMaquis() {
        const palierMax = 2;
        if (this.niveauMaquis >= palierMax) return;

        const cond = this._conditionsAgrandir();
        if (!cond.tout) {
            this.tweens.add({ targets: this.btnAgrandir, x: '+=4', duration: 50, yoyo: true, repeat: 3 });
            const manque = [];
            if (!cond.tablesOk) manque.push('tables au max');
            if (!cond.servOk)   manque.push('équipe complète');
            if (!cond.chefOk)   manque.push('chef au max');
            if (!cond.soldOk)   manque.push(`${(this.coutsAgrandir[this.niveauMaquis] ?? 0).toLocaleString('fr-FR')} cauris`);
            this.afficherMessage('Il manque : ' + manque.join(', ') + ' !');
            return;
        }

        const cout = this.coutsAgrandir[this.niveauMaquis];
        const ok = confirm(`Agrandir ton maquis pour ${cout.toLocaleString('fr-FR')} cauris ?`);
        if (!ok) return;

        this.solde -= cout;
        this.niveauMaquis++;

        // ── Données du nouveau palier (déclaré EN PREMIER pour éviter TDZ) ──
        const pData = this.palierData[this.niveauMaquis - 1] ?? this.palierData[0];

        // ── RESET PRESTIGE : repart à zéro sauf le solde ──

        // 1. Clients en cours → détruire
        this.clients.forEach(c => c.obj?.destroy());
        this.clients = [];

        // 2. Tables → tout désactiver et détruire les sprites, reposer 2 de départ
        this.tablesData.forEach(t => {
            if (t.sprite) { t.sprite.destroy(); t.sprite = null; }
            t.occupe = false;
        });
        this.tablesData.forEach((t, i) => { t.active = (i < 2); });
        this.coutTable = pData.coutTableDepart ?? 5000;

        // 3. Serveuses → tout détruire, repartir avec serveuse de départ du palier 2
        this.serveuses.forEach(s => s?.destroy());
        this.serveuses = [];
        this.coutsServeuse      = pData.coutsServeuse      ?? [0, 12000, 40000, 100000];
        this.coutsUpgradeMenu   = pData.coutsUpgradeMenu   ?? this.coutsUpgradeMenu;
        this.paiementsParNiveau = pData.paiementsParNiveau ?? this.paiementsParNiveau;
        this.spritesServeuse    = pData.spritesServeuse;
        this.spritesClients     = pData.spritesClients;

        // 4. Chef → retour niveau 1 du palier 2
        this.niveauMenu = 1;

        // 5. Remplacer le boucantier
        const bx = this.boucantier.x, by = this.boucantier.y,
              bsc = this.boucantier.scale, bdep = this.boucantier.depth;
        this.boucantier.destroy();
        this.boucantier = this.add.image(bx, by, pData.boucantierKey)
            .setOrigin(0.5, 1).setDepth(bdep).setScale(0);
        this.tweens.add({ targets: this.boucantier, scale: bsc, duration: 400, ease: 'Back.Out' });

        // 6. Mettre à jour coords tablesData depuis tablesCoords du nouveau palier AVANT de poser
        const tCoords = pData.tablesCoords;
        if (tCoords) {
            this.tablesData.forEach((t, i) => {
                if (tCoords[i]) { t.sx = tCoords[i].sx; t.sy = tCoords[i].sy; t.x = tCoords[i].x; t.y = tCoords[i].y; }
            });
        }
        // Poser les 2 tables avec les bonnes coords palier 2
        this.tablesData.forEach(t => { if (t.active) this.poserTable(t); });
        // Serveuse de départ avec position palier 2
        const pos0 = (pData.posServeuses ?? this.posServeuses)[0];
        this.serveuses.push(this.ajouterServeuse(pos0.x, pos0.y, pData.spritesServeuse[0]));

        // 7. Fond + labels
        this.majDecorMaquis(true);
        this.soldeText.setText(this.solde.toLocaleString('fr-FR') + ' cauris');
        this.btnTableLabel.setText('+ Table\n' + this.coutTable.toLocaleString('fr-FR') + ' cauris');
        this.btnServeuseLabel.setText('+ Serveuse\n12 000 cauris');
        this.btnMenuLabel.setText('Améliorer menu\n8 000 cauris');
        this.majBoutonMenu();

        this.sauvegarder();
    }

    majBtnAgrandir() {
        const palierMax = 2;
        if (this.niveauMaquis >= palierMax) {
            this.btnAgrandir.setVisible(false);
            this._tweenBtnAgrandir?.stop(); this._tweenBtnAgrandir = null;
            return;
        }
        this.btnAgrandir.setVisible(true);
        const cout = this.coutsAgrandir[this.niveauMaquis];
        this.btnAgrandirLabel.setText('🎉 Agrandir\n' + cout.toLocaleString('fr-FR') + ' cauris');
        const cond = this._conditionsAgrandir();
        this.btnAgrandirBg.setFillStyle(cond.tout ? 0x7b3f00 : 0x3a2a1a);
        this.btnAgrandir.setAlpha(cond.tout ? 1 : 0.4);
        if (cond.tout && !this._tweenBtnAgrandir) {
            this._tweenBtnAgrandir = this.tweens.add({
                targets: this.btnAgrandir, alpha: 0.7, duration: 600,
                yoyo: true, repeat: -1, ease: 'Sine.InOut',
            });
        } else if (!cond.tout && this._tweenBtnAgrandir) {
            this._tweenBtnAgrandir.stop(); this._tweenBtnAgrandir = null;
        }
    }

    animerPieces(xDepart, yDepart, nb = 5) {
        const cibleX = this.soldeText.x;
        const cibleY = this.soldeText.y;
        for (let i = 0; i < nb; i++) {
            const piece = this.add.text(xDepart, yDepart, '🐚', { fontSize: '18px' })
                .setOrigin(0.5).setDepth(15000);
            this.tweens.add({
                targets: piece,
                x: cibleX, y: cibleY,
                delay: i * 60,
                duration: 600,
                ease: 'Cubic.In',
                onComplete: () => piece.destroy(),
            });
        }
        this.time.delayedCall(nb * 60 + 600, () => {
            this.tweens.add({ targets: this.soldeText, scale: 1.2, duration: 120, yoyo: true });
        });
    }

    majSoldeAnime(nouvelleValeur) {
        const depart = this._soldeAffiche ?? this.solde;
        this._soldeAffiche = nouvelleValeur;
        this.tweens.addCounter({
            from: depart, to: nouvelleValeur, duration: 500, ease: 'Cubic.Out',
            onUpdate: (tw) => {
                const v = Math.floor(tw.getValue());
                this.soldeText.setText(v.toLocaleString('fr-FR') + ' cauris');
            },
        });
    }

    rebond(target) {
        this.tweens.add({ targets: target, scale: 0.92, duration: 80, yoyo: true });
    }

    update() {
        if (!this.pret) return;
        this.servirProchain();
        this.majBoutons();
        this.majBoutonMenu();
        this.majBtnAgrandir();
    }
}
