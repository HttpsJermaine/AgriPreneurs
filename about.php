<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us</title>
  <style>
    :root{
      --bg: #f4fbf6;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --green: #1f8f4a;
      --line: rgba(15, 23, 42, .08);
      --shadow: 0 14px 35px rgba(2, 6, 23, 0.08);
      --shadow-sm: 0 8px 18px rgba(2, 6, 23, 0.06);
      --radius: 18px;
    }
    *{ margin:0; padding:0; box-sizing:border-box; }
    body{
      font-family: "Poppins", system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color: var(--text);
    }
    .about-wrap{
      max-width: 1150px;
      margin: 40px auto 70px;
      padding: 0 18px;
    }
    .about-hero{
      border: 1px solid var(--line);
      border-radius: calc(var(--radius) + 6px);
      box-shadow: var(--shadow-sm);
      padding: 30px 24px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .about-hero::before{
      content:"";
      position:absolute;
      inset: -60px -60px auto auto;
      width: 220px;
      height: 220px;
      border-radius: 50%;
      filter: blur(2px);
    }
    .about-hero h1{
      font-size: clamp(28px, 3vw, 40px);
      letter-spacing: -0.6px;
      margin-bottom: 8px;
    }
    .about-hero p{
      max-width: 720px;
      margin: 0 auto;
      color: var(--muted);
      line-height: 1.7;
      font-size: 15px;
    }
    .section{ margin-top: 22px; }
    .card{
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      transition: transform .16s ease, box-shadow .16s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .card:hover{
      transform: translateY(-4px);
      box-shadow: var(--shadow);
    }
    .card .media{
      width: 100%;
      height: 200px;      
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      position: relative;
      overflow: visible;
    }
    .card .media img{
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      /* Use contain to show FULL image (head to suit) */
      object-fit: contain;
      object-position: center bottom;
      /* Position at bottom so shoulders align with container edge */
      align-self: flex-end;
      margin-top: auto;
    }
    /* Alternative approach: use flex alignment to push image to bottom */
    .card .media {
      display: flex;
      align-items: flex-end;
      justify-content: center;
    }
    .card .media img {
      display: block;
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
    }
    .card .body{
      padding: 14px 14px 16px;
      text-align: center;
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex: 1;
      justify-content: center;
    }
    .role{
      font-weight: 600;
      font-size: 15px;
      color: var(--text);
    }
    .sub{
      color: var(--muted);
      font-size: 14px;
      line-height: 1.45;
    }
    .featured-row{
      margin-top: 18px;
      display: flex;
      justify-content: center;
    }
    .featured-row .card{
      width: 100%;
      max-width: 260px;  
    }
    .featured-row .card .media {
      height: 220px;
    }
    .team-grid{
      margin-top: 18px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
      align-items: stretch;
    }
    @media (max-width: 1000px){
      .team-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 520px){
      .team-grid{ grid-template-columns: 1fr; }
      .featured-row .card{ max-width: 100%; }
    }
  </style>
</head>

<body>
  <header>
    <?php require 'header.php'; ?>
  </header>

  <div class="about-wrap">

    <div class="about-hero">
      <h1>About Us</h1>
      <p>Meet the people behind the system — building a smooth, secure, and farmer-friendly marketplace experience.</p>
    </div>

    <div class="featured-row">
      <div class="card">
        <div class="media">
          <img src="images/4.jpg" alt="Jermaine Buendia">
        </div>
        <div class="body">
          <div class="role">Programmer</div>
          <div class="sub">Jermaine Buendia</div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="team-grid">

        <div class="card">
          <div class="media">
            <img src="images/3.png" alt="Jane Frecious Berceles">
          </div>
          <div class="body">
            <div class="role">Systems Analyst</div>
            <div class="sub">Jane Frecious Berceles</div>
          </div>
        </div>

        <div class="card">
          <div class="media">
            <img src="images/5.jpg" alt="Monique Carating">
          </div>
          <div class="body">
            <div class="role">Technical Writer</div>
            <div class="sub">Monique Carating</div>
          </div>
        </div>

        <div class="card">
          <div class="media">
            <img src="images/2.jpg" alt="Mark Rafael Naguit">
          </div>
          <div class="body">
            <div class="role">Programmer</div>
            <div class="sub">Mark Rafael Naguit</div>
          </div>
        </div>

        <div class="card">
          <div class="media">
            <img src="images/1.jpg" alt="Drex Cajucom">
          </div>
          <div class="body">
            <div class="role">Technical Writer</div>
            <div class="sub">Drex Cajucom</div>
          </div>
        </div>

      </div>
    </div>
  </div>
  <?php require 'footer.php'; ?>
</body>
</html>